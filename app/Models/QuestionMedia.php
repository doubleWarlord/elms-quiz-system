<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionMedia extends Model
{
    protected $fillable = [
        'question_id',
        'media_type',
        'media_path',
        'media_url',
        'description',
        'requires_completion',
        'player_layout',
        'player_caption',
        'poster_path',
        'order',
    ];

    protected $casts = [
        'requires_completion' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
