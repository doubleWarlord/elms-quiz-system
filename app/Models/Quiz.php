<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quiz extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'pass_percentage',
        'attempts_allowed',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function studentQuizzes(): HasMany
    {
        return $this->hasMany(StudentQuiz::class);
    }
}
