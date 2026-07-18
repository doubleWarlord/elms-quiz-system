<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return redirect('/auth/login');
})->name('login');

Route::get('/', function () {
    return response()->json([
        'name' => 'ELMS Quiz System API',
        'status' => 'ok',
    ]);
});
