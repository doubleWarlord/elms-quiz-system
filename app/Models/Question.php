<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = [
        'quiz_id',
        'question_text',
        'type',
        'order',
        'explanation',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(QuestionMedia::class)->orderBy('order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('order');
    }

    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }
}
