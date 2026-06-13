<?php

namespace App\Providers;

use App\Models\Quiz;
use App\Policies\QuizPolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Quiz::class => QuizPolicy::class,
    ];

    public function boot(): void
    {
    }
}
