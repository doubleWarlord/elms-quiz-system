<?php

namespace App\Http\Controllers\Api;

use App\Models\StudentAnswer;
use App\Models\StudentQuiz;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentQuizController extends Controller
{
    public function start(Request $request, Quiz $quiz)
    {
        $user = Auth::user();

        $studentQuiz = StudentQuiz::firstOrCreate(
            [
                'student_id' => $user->id,
                'quiz_id' => $quiz->id,
            ],
            [
                'total_questions' => $quiz->questions->count(),
                'status' => 'in_progress',
                'started_at' => now(),
            ]
        );

        return response()->json($studentQuiz);
    }

    public function getCurrentQuestion(Quiz $quiz)
    {
        $user = Auth::user();
        $studentQuiz = StudentQuiz::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->firstOrFail();

        $answeredQuestions = StudentAnswer::where('student_quiz_id', $studentQuiz->id)
            ->distinct()
            ->pluck('question_id')
            ->toArray();

        $currentQuestion = $quiz->questions()
            ->whereNotIn('id', $answeredQuestions)
            ->orWhereNotIn('id', 
                StudentAnswer::where('student_quiz_id', $studentQuiz->id)
                    ->where('is_correct', true)
                    ->distinct()
                    ->pluck('question_id')
            )
            ->first();

        if (!$currentQuestion) {
            return response()->json(['message' => 'Quiz completed'], 200);
        }

        $currentQuestion->load('media', 'answers');

        return response()->json($currentQuestion);
    }

    public function submitAnswer(Request $request, Quiz $quiz)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_id' => 'nullable|exists:answers,id',
            'student_answer' => 'nullable|string',
        ]);

        $studentQuiz = StudentQuiz::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->firstOrFail();

        $question = $quiz->questions()->findOrFail($validated['question_id']);
        $isCorrect = false;

        if ($validated['answer_id']) {
            $answer = $question->answers()->findOrFail($validated['answer_id']);
            $isCorrect = $answer->is_correct;
        }

        $studentAnswer = StudentAnswer::create([
            'student_quiz_id' => $studentQuiz->id,
            'question_id' => $validated['question_id'],
            'answer_id' => $validated['answer_id'] ?? null,
            'student_answer' => $validated['student_answer'] ?? null,
            'is_correct' => $isCorrect,
        ]);

        if (!$isCorrect) {
            return response()->json([
                'message' => 'Incorrect answer. Please try again.',
                'is_correct' => false,
                'explanation' => $question->explanation,
            ]);
        }

        return response()->json([
            'message' => 'Correct answer!',
            'is_correct' => true,
            'explanation' => $question->explanation,
        ]);
    }

    public function getResults(Quiz $quiz)
    {
        $user = Auth::user();
        $studentQuiz = StudentQuiz::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->firstOrFail();

        $correctAnswers = StudentAnswer::where('student_quiz_id', $studentQuiz->id)
            ->where('is_correct', true)
            ->distinct('question_id')
            ->count();

        $totalQuestions = $quiz->questions->count();
        $score = ($correctAnswers / $totalQuestions) * 100;
        $passed = $score >= $quiz->pass_percentage;

        $studentQuiz->update([
            'correct_answers' => $correctAnswers,
            'score' => (int) $score,
            'passed' => $passed,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'quiz' => $quiz,
            'student_quiz' => $studentQuiz,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'score' => (int) $score,
            'passed' => $passed,
        ]);
    }
}
