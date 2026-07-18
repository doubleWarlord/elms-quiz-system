<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\StudentQuizController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseLessonController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);

    // Quiz Routes
    Route::get('/quizzes', [QuizController::class, 'index']);
    Route::post('/quizzes', [QuizController::class, 'store']);
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
    Route::put('/quizzes/{quiz}', [QuizController::class, 'update']);
    Route::post('/quizzes/{quiz}/certificate-logo', [QuizController::class, 'uploadCertificateLogo']);
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy']);

    // Question Routes
    Route::post('/quizzes/{quiz}/questions', [QuestionController::class, 'store']);
    Route::put('/questions/{question}', [QuestionController::class, 'update']);
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);
    Route::post('/questions/{question}/media', [QuestionController::class, 'addMedia']);

    // Answer Routes
    Route::post('/questions/{question}/answers', [AnswerController::class, 'store']);
    Route::put('/answers/{answer}', [AnswerController::class, 'update']);
    Route::delete('/answers/{answer}', [AnswerController::class, 'destroy']);

    // Admin User Routes
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{user}', [UserController::class, 'update']);
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);

    // Student Quiz Routes
    Route::post('/quizzes/{quiz}/start', [StudentQuizController::class, 'start']);
    Route::get('/quizzes/{quiz}/current-question', [StudentQuizController::class, 'getCurrentQuestion']);
    Route::post('/quizzes/{quiz}/submit-answer', [StudentQuizController::class, 'submitAnswer']);
    Route::get('/quizzes/{quiz}/results', [StudentQuizController::class, 'getResults']);
    Route::get('/quizzes/{quiz}/certificate', [StudentQuizController::class, 'getCertificate']);
    Route::get('/quizzes/{quiz}/certificate/download', [StudentQuizController::class, 'downloadCertificatePdf']);

    // ── Courses ───────────────────────────────────────────────────────────────
    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/enrolled', [CourseController::class, 'myEnrolled']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::put('/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
    Route::post('/courses/{course}/cover', [CourseController::class, 'uploadCover']);
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll']);
    Route::get('/courses/{course}/progress', [CourseLessonController::class, 'getProgress']);

    // Modules
    Route::post('/courses/{course}/modules', [CourseLessonController::class, 'storeModule']);
    Route::put('/modules/{module}', [CourseLessonController::class, 'updateModule']);
    Route::delete('/modules/{module}', [CourseLessonController::class, 'destroyModule']);

    // Lessons
    Route::post('/modules/{module}/lessons', [CourseLessonController::class, 'storeLesson']);
    Route::put('/lessons/{lesson}', [CourseLessonController::class, 'updateLesson']);
    Route::delete('/lessons/{lesson}', [CourseLessonController::class, 'destroyLesson']);
    Route::post('/lessons/{lesson}/complete', [CourseLessonController::class, 'markComplete']);
});
