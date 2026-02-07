<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Buzzer');
});

Route::get('/buzzer', function () {
    return redirect('/');
});

Route::get('/admin', function () {
    return Inertia::render('BuzzAdmin');
});
