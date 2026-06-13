<?php

namespace App\Http\Controllers\Api;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuestionMedia;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function store(Request $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,short_answer',
            'explanation' => 'nullable|string',
        ]);

        $maxOrder = Question::where('quiz_id', $quiz->id)->max('order') ?? 0;

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => $validated['question_text'],
            'type' => $validated['type'],
            'order' => $maxOrder + 1,
            'explanation' => $validated['explanation'] ?? null,
        ]);

        return response()->json($question, 201);
    }

    public function update(Request $request, Question $question)
    {
        $this->authorize('update', $question->quiz);

        $validated = $request->validate([
            'question_text' => 'string',
            'type' => 'in:multiple_choice,true_false,short_answer',
            'explanation' => 'nullable|string',
            'order' => 'integer|min:1',
        ]);

        $question->update($validated);

        return response()->json($question);
    }

    public function destroy(Question $question)
    {
        $this->authorize('update', $question->quiz);
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }

    public function addMedia(Request $request, Question $question)
    {
        $this->authorize('update', $question->quiz);

        $validated = $request->validate([
            'media_type' => 'required|in:text,image,video,document',
            'media_path' => 'nullable|string',
            'media_url' => 'nullable|url',
            'description' => 'nullable|string',
        ]);

        $maxOrder = QuestionMedia::where('question_id', $question->id)->max('order') ?? 0;

        $media = QuestionMedia::create([
            'question_id' => $question->id,
            'media_type' => $validated['media_type'],
            'media_path' => $validated['media_path'] ?? null,
            'media_url' => $validated['media_url'] ?? null,
            'description' => $validated['description'] ?? null,
            'order' => $maxOrder + 1,
        ]);

        return response()->json($media, 201);
    }
}
