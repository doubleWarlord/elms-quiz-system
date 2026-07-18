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
        'pass_notification_enabled',
        'pass_notification_cc_email',
        'certificate_enabled',
        'certificate_template_title',
        'certificate_template_body',
        'certificate_template_footer',
        'certificate_logo_path',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'pass_notification_enabled' => 'boolean',
        'certificate_enabled' => 'boolean',
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
