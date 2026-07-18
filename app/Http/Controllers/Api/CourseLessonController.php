<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CourseLessonController extends Controller
{
    // ── Modules ─────────────────────────────────────────────────────────────

    public function storeModule(Request $request, Course $course)
    {
        $this->assertCanManage($course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $maxOrder = CourseModule::where('course_id', $course->id)->max('order') ?? 0;

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'order' => $maxOrder + 1,
        ]);

        return response()->json($module->load('lessons'), 201);
    }

    public function updateModule(Request $request, CourseModule $module)
    {
        $this->assertCanManage($module->course);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'order' => 'integer|min:1',
        ]);

        $module->update($validated);
        return response()->json($module);
    }

    public function destroyModule(CourseModule $module)
    {
        $this->assertCanManage($module->course);
        $module->delete();
        return response()->json(['message' => 'Module deleted']);
    }

    // ── Lessons ──────────────────────────────────────────────────────────────

    public function storeLesson(Request $request, CourseModule $module)
    {
        $this->assertCanManage($module->course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'lesson_type' => 'required|in:text,video,audio,slide,document',
            'media_url' => 'nullable|url',
            'requires_completion' => 'boolean',
            'quiz_id' => 'nullable|exists:quizzes,id',
            'duration_minutes' => 'integer|min:0',
            'file' => 'nullable|file|max:51200',
            'poster_file' => 'nullable|image|max:5120',
        ]);

        $mediaPath = null;
        $posterPath = null;

        if ($request->hasFile('file')) {
            $stored = $request->file('file')->store('lesson-media', 'public');
            $mediaPath = Storage::url($stored);
        }

        if ($request->hasFile('poster_file')) {
            $stored = $request->file('poster_file')->store('lesson-posters', 'public');
            $posterPath = Storage::url($stored);
        }

        $maxOrder = CourseLesson::where('module_id', $module->id)->max('order') ?? 0;

        $lesson = CourseLesson::create([
            'module_id' => $module->id,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'lesson_type' => $validated['lesson_type'],
            'media_path' => $mediaPath,
            'media_url' => $validated['media_url'] ?? null,
            'poster_path' => $posterPath,
            'requires_completion' => $validated['requires_completion'] ?? false,
            'quiz_id' => $validated['quiz_id'] ?? null,
            'order' => $maxOrder + 1,
            'duration_minutes' => $validated['duration_minutes'] ?? 0,
        ]);

        return response()->json($lesson, 201);
    }

    public function updateLesson(Request $request, CourseLesson $lesson)
    {
        $this->assertCanManage($lesson->module->course);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'content' => 'nullable|string',
            'lesson_type' => 'in:text,video,audio,slide,document',
            'media_url' => 'nullable|url',
            'requires_completion' => 'boolean',
            'quiz_id' => 'nullable|exists:quizzes,id',
            'duration_minutes' => 'integer|min:0',
            'order' => 'integer|min:1',
        ]);

        $lesson->update($validated);
        return response()->json($lesson);
    }

    public function destroyLesson(CourseLesson $lesson)
    {
        $this->assertCanManage($lesson->module->course);
        $lesson->delete();
        return response()->json(['message' => 'Lesson deleted']);
    }

    // ── Progress ─────────────────────────────────────────────────────────────

    public function markComplete(Request $request, CourseLesson $lesson)
    {
        $user = Auth::user();

        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $lesson->module->course_id)
            ->firstOrFail();

        $progress = LessonProgress::firstOrCreate(
            ['student_id' => $user->id, 'lesson_id' => $lesson->id],
            ['enrollment_id' => $enrollment->id, 'started_at' => now()]
        );

        if (!$progress->completed_at) {
            $progress->update(['completed_at' => now()]);
        }

        // Check if entire course is now complete
        $course = $lesson->module->course;
        $totalLessons = $course->lessons()->count();
        $completedLessons = LessonProgress::where('student_id', $user->id)
            ->whereIn('lesson_id', $course->lessons()->pluck('id'))
            ->whereNotNull('completed_at')
            ->count();

        if ($totalLessons > 0 && $completedLessons >= $totalLessons && !$enrollment->completed_at) {
            $enrollment->update(['completed_at' => now()]);
        }

        return response()->json([
            'message' => 'Lesson marked complete',
            'progress' => $progress,
            'course_completed' => $totalLessons > 0 && $completedLessons >= $totalLessons,
        ]);
    }

    public function getProgress(Course $course)
    {
        $user = Auth::user();

        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $lessonIds = $course->lessons()->pluck('id');
        $progress = LessonProgress::where('student_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');

        return response()->json([
            'enrollment' => $enrollment,
            'progress' => $progress,
            'total_lessons' => $lessonIds->count(),
            'completed_lessons' => $progress->whereNotNull('completed_at')->count(),
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function assertCanManage(Course $course): void
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $course->user_id === $user->id, 403);
    }
}
