<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAnswer extends Model
{
    protected $fillable = [
        'student_quiz_id',
        'question_id',
        'answer_id',
        'student_answer',
        'is_correct',
        'attempt_number',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function studentQuiz(): BelongsTo
    {
        return $this->belongsTo(StudentQuiz::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
