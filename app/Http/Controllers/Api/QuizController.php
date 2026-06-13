<?php

namespace App\Http\Controllers\Api;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $this->authorize('isTeacher', Auth::user());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pass_percentage' => 'required|integer|min:0|max:100',
            'attempts_allowed' => 'required|integer|min:0',
            'is_published' => 'boolean',
        ]);

        $quiz = Quiz::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'pass_percentage' => $validated['pass_percentage'],
            'attempts_allowed' => $validated['attempts_allowed'],
            'is_published' => $validated['is_published'] ?? false,
        ]);

        return response()->json($quiz, 201);
    }

    public function show(Quiz $quiz)
    {
        $quiz->load('questions.media', 'questions.answers');
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
}
