<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\StudentQuizController;

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

    // Student Quiz Routes
    Route::post('/quizzes/{quiz}/start', [StudentQuizController::class, 'start']);
    Route::get('/quizzes/{quiz}/current-question', [StudentQuizController::class, 'getCurrentQuestion']);
    Route::post('/quizzes/{quiz}/submit-answer', [StudentQuizController::class, 'submitAnswer']);
    Route::get('/quizzes/{quiz}/results', [StudentQuizController::class, 'getResults']);
});
