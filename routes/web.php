<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Support\Facades\Artisan;
Route::get('/setup-db', function () {
    Artisan::call('migrate:fresh', ['--force' => true]);
    return 'Database Migration Completed Successfully!';
});
