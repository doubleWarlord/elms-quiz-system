<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable();          // rich text / slide text
            $table->enum('lesson_type', ['text', 'video', 'audio', 'slide', 'document'])->default('text');
            $table->string('media_path')->nullable();     // uploaded file URL
            $table->string('media_url')->nullable();      // external URL
            $table->string('poster_path')->nullable();    // video poster
            $table->boolean('requires_completion')->default(false); // must finish video
            $table->unsignedBigInteger('quiz_id')->nullable(); // optional linked quiz
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('set null');
            $table->integer('order')->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lessons');
    }
};
