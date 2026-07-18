<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isTeacher()) {
            $courses = Course::where('user_id', $user->id)
                ->withCount('enrollments')
                ->with('modules.lessons')
                ->get();
        } elseif ($user->isAdmin()) {
            $courses = Course::withCount('enrollments')
                ->with('modules.lessons')
                ->get();
        } else {
            // Students see published courses with their enrollment status
            $courses = Course::where('is_published', true)
                ->withCount('enrollments')
                ->with('modules.lessons')
                ->get()
                ->map(function ($course) use ($user) {
                    $enrollment = Enrollment::where('student_id', $user->id)
                        ->where('course_id', $course->id)
                        ->first();
                    $course->enrollment = $enrollment;
                    return $course;
                });
        }

        return response()->json($courses);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()?->isTeacher() || Auth::user()?->isAdmin(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'level' => 'in:beginner,intermediate,advanced',
            'duration_minutes' => 'integer|min:0',
            'is_published' => 'boolean',
        ]);

        $course = Course::create(array_merge($validated, [
            'user_id' => Auth::id(),
            'is_published' => $validated['is_published'] ?? false,
        ]));

        return response()->json($course->load('modules.lessons'), 201);
    }

    public function show(Course $course)
    {
        $user = Auth::user();
        $canManage = $user->isAdmin() || $course->user_id === $user->id;
        abort_unless($canManage || $course->is_published, 403);

        $course->load('modules.lessons', 'creator');

        if (!$canManage) {
            $enrollment = Enrollment::where('student_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            $course->enrollment = $enrollment;
        }

        return response()->json($course);
    }

    public function update(Request $request, Course $course)
    {
        abort_unless(Auth::user()->isAdmin() || $course->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'level' => 'in:beginner,intermediate,advanced',
            'duration_minutes' => 'integer|min:0',
            'is_published' => 'boolean',
        ]);

        $course->update($validated);

        return response()->json($course->load('modules.lessons'));
    }

    public function destroy(Course $course)
    {
        abort_unless(Auth::user()->isAdmin() || $course->user_id === Auth::id(), 403);
        $course->delete();
        return response()->json(['message' => 'Course deleted']);
    }

    public function uploadCover(Request $request, Course $course)
    {
        abort_unless(Auth::user()->isAdmin() || $course->user_id === Auth::id(), 403);

        $request->validate([
            'cover' => 'required|image|max:5120|mimes:jpg,jpeg,png,webp',
        ]);

        $path = $request->file('cover')->store('course-covers', 'public');
        $url = Storage::url($path);
        $course->update(['cover_image' => $url]);

        return response()->json(['cover_image' => $url]);
    }

    public function enroll(Course $course)
    {
        $user = Auth::user();
        abort_unless($course->is_published, 403, 'This course is not published.');

        $existing = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already enrolled', 'enrollment' => $existing]);
        }

        $enrollment = Enrollment::create([
            'student_id' => $user->id,
            'course_id' => $course->id,
        ]);

        return response()->json(['message' => 'Enrolled successfully', 'enrollment' => $enrollment], 201);
    }

    public function myEnrolled()
    {
        $user = Auth::user();

        $enrollments = Enrollment::where('student_id', $user->id)
            ->with(['course.modules.lessons'])
            ->get()
            ->map(function ($enrollment) use ($user) {
                $course = $enrollment->course;
                $totalLessons = $course->lessons()->count();
                $completedLessons = \App\Models\LessonProgress::where('student_id', $user->id)
                    ->whereIn('lesson_id', $course->lessons()->pluck('id'))
                    ->whereNotNull('completed_at')
                    ->count();

                $course->total_lessons = $totalLessons;
                $course->completed_lessons = $completedLessons;
                $course->progress_percent = $totalLessons > 0
                    ? (int) round(($completedLessons / $totalLessons) * 100)
                    : 0;
                $course->enrollment = $enrollment;

                return $course;
            });

        return response()->json($enrollments);
    }
}
