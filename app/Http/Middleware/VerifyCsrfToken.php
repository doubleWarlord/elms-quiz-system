<?php

namespace App\Http\Middleware;

Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'api/*',
    ];
}
