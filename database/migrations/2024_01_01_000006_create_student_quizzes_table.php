<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('score')->nullable();
            $table->integer('total_questions');
            $table->integer('correct_answers')->nullable();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->boolean('passed')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_quizzes');
    }
};
