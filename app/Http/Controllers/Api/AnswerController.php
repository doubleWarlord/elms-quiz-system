<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    public function store(Request $request, Question $question)
    {
        $this->authorize('update', $question->quiz);

        $validated = $request->validate([
            'answer_text' => 'required|string',
            'is_correct' => 'required|boolean',
        ]);

        $maxOrder = Answer::where('question_id', $question->id)->max('order') ?? 0;

        $answer = Answer::create([
            'question_id' => $question->id,
            'answer_text' => $validated['answer_text'],
            'is_correct' => $validated['is_correct'],
            'order' => $maxOrder + 1,
        ]);

        return response()->json($answer, 201);
    }

    public function update(Request $request, Answer $answer)
    {
        $this->authorize('update', $answer->question->quiz);

        $validated = $request->validate([
            'answer_text' => 'string',
            'is_correct' => 'boolean',
            'order' => 'integer|min:1',
        ]);

        $answer->update($validated);

        return response()->json($answer);
    }

    public function destroy(Answer $answer)
    {
        $this->authorize('update', $answer->question->quiz);
        $answer->delete();
        return response()->json(['message' => 'Answer deleted successfully']);
    }
}
