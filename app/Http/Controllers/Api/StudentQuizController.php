<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\StudentAnswer;
use App\Models\StudentQuiz;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StudentQuizController extends Controller
{
    public function start(Request $request, Quiz $quiz)
    {
        $user = Auth::user();
        $canManageQuiz = $this->canManageQuiz($quiz, $user);

        abort_unless($canManageQuiz || $quiz->is_published, 403);

        $hasPassedAttempt = StudentQuiz::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('passed', true)
            ->exists();

        if ($hasPassedAttempt) {
            abort(403, 'You have already passed this quiz. Retake is not required.');
        }

        // Check if quiz has been started before
        $lastAttempt = StudentQuiz::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->orderBy('attempt_number', 'desc')
            ->first();

        $attemptNumber = $lastAttempt ? $lastAttempt->attempt_number + 1 : 1;

        // Check if attempts are allowed
        if ($quiz->attempts_allowed > 0 && $attemptNumber > $quiz->attempts_allowed) {
            abort(403, 'Maximum quiz attempts reached.');
        }

        // Check if last attempt is still in progress (don't allow new attempt until completed)
        if ($lastAttempt && $lastAttempt->status === 'in_progress') {
            return response()->json($lastAttempt);
        }

        $studentQuiz = StudentQuiz::create([
            'student_id' => $user->id,
            'quiz_id' => $quiz->id,
            'attempt_number' => $attemptNumber,
            'total_questions' => $quiz->questions->count(),
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json($studentQuiz);
    }

    public function getCurrentQuestion(Quiz $quiz)
    {
        $user = Auth::user();
        $canManageQuiz = $this->canManageQuiz($quiz, $user);

        abort_unless($canManageQuiz || $quiz->is_published, 403);

        $studentQuiz = StudentQuiz::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->orderBy('attempt_number', 'desc')
            ->firstOrFail();

        $answeredQuestionIds = StudentAnswer::where('student_quiz_id', $studentQuiz->id)
            ->distinct()
            ->pluck('question_id');

        $currentQuestion = $quiz->questions()
            ->whereNotIn('id', $answeredQuestionIds)
            ->orderBy('order')
            ->first();

        if (!$currentQuestion) {
            return response()->json(['message' => 'Quiz completed'], 200);
        }

        $currentQuestion->load('media');
        $currentQuestion->load([
            'answers' => fn ($query) => $canManageQuiz
                ? $query
                : $query->select('id', 'question_id', 'answer_text', 'order'),
        ]);

        return response()->json($currentQuestion);
    }

    public function submitAnswer(Request $request, Quiz $quiz)
    {
        $user = Auth::user();
        $canManageQuiz = $this->canManageQuiz($quiz, $user);

        abort_unless($canManageQuiz || $quiz->is_published, 403);

        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_id' => 'nullable|exists:answers,id',
            'student_answer' => 'nullable|string',
        ]);

        $studentQuiz = StudentQuiz::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->orderBy('attempt_number', 'desc')
            ->firstOrFail();

        $question = $quiz->questions()->findOrFail($validated['question_id']);

        $alreadyAnswered = StudentAnswer::where('student_quiz_id', $studentQuiz->id)
            ->where('question_id', $question->id)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json([
                'message' => 'This question has already been answered. Moving to next question.',
                'is_correct' => false,
                'already_answered' => true,
            ], 200);
        }

        $isCorrect = false;

        if ($validated['answer_id']) {
            $answer = $question->answers()->findOrFail($validated['answer_id']);
            $isCorrect = $answer->is_correct;
        }

        StudentAnswer::create([
            'student_quiz_id' => $studentQuiz->id,
            'question_id' => $validated['question_id'],
            'answer_id' => $validated['answer_id'] ?? null,
            'student_answer' => $validated['student_answer'] ?? null,
            'is_correct' => $isCorrect,
        ]);

        if (!$isCorrect) {
            return response()->json([
                'message' => 'Incorrect answer recorded. Moving to next question.',
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
        $canManageQuiz = $this->canManageQuiz($quiz, $user);

        abort_unless($canManageQuiz || $quiz->is_published, 403);

        $studentQuiz = StudentQuiz::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->orderBy('attempt_number', 'desc')
            ->firstOrFail();

        $correctAnswers = StudentAnswer::where('student_quiz_id', $studentQuiz->id)
            ->where('is_correct', true)
            ->distinct('question_id')
            ->count();

        $totalQuestions = $quiz->questions->count();
        $score = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        $passed = $score >= $quiz->pass_percentage;

        $studentQuiz->update([
            'correct_answers' => $correctAnswers,
            'score' => (int) $score,
            'passed' => $passed,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $studentQuiz->refresh();

        if ($passed && $quiz->certificate_enabled) {
            if (!$studentQuiz->certificate_code) {
                $studentQuiz->update([
                    'certificate_code' => $this->generateCertificateCode($quiz->id, $studentQuiz->id),
                    'certificate_generated_at' => now(),
                ]);
                $studentQuiz->refresh();
            }
        }

        if ($passed && $quiz->pass_notification_enabled && !$studentQuiz->pass_notification_sent_at) {
            try {
                $certificatePayload = $quiz->certificate_enabled
                    ? $this->buildCertificatePayload($quiz, $studentQuiz, $user, (int) $score)
                    : null;

                Mail::send('emails.quiz_passed', [
                    'studentName' => $user->name,
                    'quizTitle' => $quiz->title,
                    'score' => (int) $score,
                    'passPercentage' => $quiz->pass_percentage,
                    'attemptNumber' => $studentQuiz->attempt_number,
                    'certificateEnabled' => $quiz->certificate_enabled,
                    'certificateCode' => $studentQuiz->certificate_code,
                    'certificateTitle' => $certificatePayload['title'] ?? null,
                    'certificateBody' => $certificatePayload['body'] ?? null,
                    'certificateFooter' => $certificatePayload['footer'] ?? null,
                ], function ($message) use ($user, $quiz) {
                    $message->to($user->email)
                        ->subject('Quiz Passed: ' . $quiz->title);

                    if (!empty($quiz->pass_notification_cc_email)) {
                        $message->cc($quiz->pass_notification_cc_email);
                    }
                });

                $studentQuiz->update([
                    'pass_notification_sent_at' => now(),
                ]);
                $studentQuiz->refresh();
            } catch (\Throwable $exception) {
                Log::error('Failed to send pass notification email', [
                    'student_quiz_id' => $studentQuiz->id,
                    'quiz_id' => $quiz->id,
                    'student_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'quiz' => $quiz,
            'student_quiz' => $studentQuiz,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'score' => (int) $score,
            'passed' => $passed,
        ]);
    }

    public function getCertificate(Quiz $quiz)
    {
        return response()->json(
            $this->buildCertificateResponsePayload($quiz, Auth::user())
        );
    }

    public function downloadCertificatePdf(Quiz $quiz)
    {
        $payload = $this->buildCertificateResponsePayload($quiz, Auth::user());

        $pdf = Pdf::loadView('certificates.certificate_pdf', [
            'quiz' => $payload['quiz'],
            'student' => $payload['student'],
            'studentQuiz' => [
                'score' => $payload['student_quiz']['score'],
                'certificate_code' => $payload['student_quiz']['certificate_code'],
                'completed_at' => optional($payload['student_quiz']['completed_at'])->format('Y-m-d H:i:s'),
            ],
            'certificate' => $payload['certificate'],
            'logoPath' => $this->resolvePdfLogoPath($payload['certificate']['logo_url'] ?? null),
        ]);

        $fileName = 'certificate-quiz-' . $quiz->id . '-attempt-' . $payload['student_quiz']['attempt_number'] . '.pdf';

        return $pdf->download($fileName);
    }

    private function buildCertificateResponsePayload(Quiz $quiz, $user): array
    {
        $canManageQuiz = $this->canManageQuiz($quiz, $user);

        abort_unless($canManageQuiz || $quiz->is_published, 403);
        abort_unless($quiz->certificate_enabled, 403, 'Certificate is disabled for this quiz.');

        $studentQuiz = StudentQuiz::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderBy('attempt_number', 'desc')
            ->firstOrFail();

        abort_unless($studentQuiz->passed, 403, 'Certificate is only available for passed attempts.');

        if (!$studentQuiz->certificate_code) {
            $studentQuiz->update([
                'certificate_code' => $this->generateCertificateCode($quiz->id, $studentQuiz->id),
                'certificate_generated_at' => now(),
            ]);
            $studentQuiz->refresh();
        }

        $certificatePayload = $this->buildCertificatePayload($quiz, $studentQuiz, $user, (int) $studentQuiz->score);

        return [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
            ],
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'student_quiz' => $studentQuiz,
            'certificate' => $certificatePayload,
        ];
    }

    private function generateCertificateCode(int $quizId, int $studentQuizId): string
    {
        return sprintf(
            'CERT-%d-%d-%s',
            $quizId,
            $studentQuizId,
            now()->format('YmdHis')
        );
    }

    private function buildCertificatePayload(Quiz $quiz, StudentQuiz $studentQuiz, $user, int $score): array
    {
        $titleTemplate = $quiz->certificate_template_title ?: 'Certificate of Achievement';
        $bodyTemplate = $quiz->certificate_template_body
            ?: 'This certifies that {student_name} has successfully passed "{quiz_title}" with a score of {score}% on {completed_at}. Certificate code: {certificate_code}.';
        $footerTemplate = $quiz->certificate_template_footer ?: 'Authorized by {app_name}';

        $tokens = [
            '{student_name}' => $user->name,
            '{student_email}' => $user->email,
            '{quiz_title}' => $quiz->title,
            '{score}' => (string) $score,
            '{pass_mark}' => (string) $quiz->pass_percentage,
            '{attempt_number}' => (string) $studentQuiz->attempt_number,
            '{certificate_code}' => (string) $studentQuiz->certificate_code,
            '{completed_at}' => optional($studentQuiz->completed_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
            '{app_name}' => config('app.name'),
        ];

        return [
            'title' => strtr($titleTemplate, $tokens),
            'body' => strtr($bodyTemplate, $tokens),
            'footer' => strtr($footerTemplate, $tokens),
            'logo_url' => $quiz->certificate_logo_path,
            'tokens' => $tokens,
        ];
    }

    private function resolvePdfLogoPath(?string $logoUrl): ?string
    {
        if (!$logoUrl) {
            return null;
        }

        if (Str::startsWith($logoUrl, '/storage/')) {
            return public_path($logoUrl);
        }

        return $logoUrl;
    }

    private function canManageQuiz(Quiz $quiz, $user): bool
    {
        return $user->isAdmin() || $quiz->user_id === $user->id;
    }
}
