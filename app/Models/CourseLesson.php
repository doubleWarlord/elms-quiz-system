<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLesson extends Model
{
    protected $fillable = [
        'module_id', 'title', 'content', 'lesson_type',
        'media_path', 'media_url', 'poster_path',
        'requires_completion', 'quiz_id', 'order', 'duration_minutes',
    ];

    protected $casts = [
        'requires_completion' => 'boolean',
        'duration_minutes' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function progress(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LessonProgress::class, 'lesson_id');
    }
}
