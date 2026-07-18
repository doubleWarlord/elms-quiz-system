<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentQuiz extends Model
{
    protected $fillable = [
        'student_id',
        'quiz_id',
        'attempt_number',
        'started_at',
        'completed_at',
        'score',
        'total_questions',
        'correct_answers',
        'status',
        'passed',
        'certificate_code',
        'certificate_generated_at',
        'pass_notification_sent_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'passed' => 'boolean',
        'certificate_generated_at' => 'datetime',
        'pass_notification_sent_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }
}
