<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class QuizController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isTeacher()) {
            $quizzes = Quiz::where('user_id', $user->id)->with('questions')->get();
        } else {
            $quizzes = Quiz::where('is_published', true)->with('questions')->get();
        }

        return response()->json($quizzes);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()?->isTeacher() || Auth::user()?->isAdmin(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pass_percentage' => 'required|integer|min:0|max:100',
            'attempts_allowed' => 'required|integer|min:0',
            'is_published' => 'boolean',
            'pass_notification_enabled' => 'boolean',
            'pass_notification_cc_email' => 'nullable|email',
            'certificate_enabled' => 'boolean',
            'certificate_template_title' => 'nullable|string|max:255',
            'certificate_template_body' => 'nullable|string',
            'certificate_template_footer' => 'nullable|string|max:255',
            'certificate_logo_path' => 'nullable|string|max:255',
        ]);

        $quiz = Quiz::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'pass_percentage' => $validated['pass_percentage'],
            'attempts_allowed' => $validated['attempts_allowed'],
            'is_published' => $validated['is_published'] ?? false,
            'pass_notification_enabled' => $validated['pass_notification_enabled'] ?? true,
            'pass_notification_cc_email' => $validated['pass_notification_cc_email'] ?? null,
            'certificate_enabled' => $validated['certificate_enabled'] ?? true,
            'certificate_template_title' => $validated['certificate_template_title'] ?? 'Certificate of Achievement',
            'certificate_template_body' => $validated['certificate_template_body'] ?? null,
            'certificate_template_footer' => $validated['certificate_template_footer'] ?? null,
            'certificate_logo_path' => $validated['certificate_logo_path'] ?? null,
        ]);

        return response()->json($quiz, 201);
    }

    public function show(Quiz $quiz)
    {
        $user = Auth::user();
        $canManageQuiz = $user->isAdmin() || $quiz->user_id === $user->id;

        abort_unless($canManageQuiz || $quiz->is_published, 403);

        if ($canManageQuiz) {
            $quiz->load('questions.media', 'questions.answers');
        } else {
            $quiz->load([
                'questions.media',
                'questions.answers' => fn ($query) => $query->select('id', 'question_id', 'answer_text', 'order'),
            ]);
        }

        return response()->json($quiz);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'pass_percentage' => 'integer|min:0|max:100',
            'attempts_allowed' => 'integer|min:0',
            'is_published' => 'boolean',
            'pass_notification_enabled' => 'boolean',
            'pass_notification_cc_email' => 'nullable|email',
            'certificate_enabled' => 'boolean',
            'certificate_template_title' => 'nullable|string|max:255',
            'certificate_template_body' => 'nullable|string',
            'certificate_template_footer' => 'nullable|string|max:255',
            'certificate_logo_path' => 'nullable|string|max:255',
        ]);

        $quiz->update($validated);

        return response()->json($quiz);
    }

    public function destroy(Quiz $quiz)
    {
        $this->authorize('delete', $quiz);
        $quiz->delete();
        return response()->json(['message' => 'Quiz deleted successfully']);
    }

    public function uploadCertificateLogo(Request $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'logo' => 'required|image|max:5120|mimes:jpg,jpeg,png,webp,svg',
        ]);

        $storedPath = $validated['logo']->store('certificate-logos', 'public');
        $logoPath = Storage::url($storedPath);

        $quiz->update([
            'certificate_logo_path' => $logoPath,
        ]);

        return response()->json([
            'message' => 'Certificate logo uploaded successfully.',
            'certificate_logo_path' => $logoPath,
        ]);
    }
}
