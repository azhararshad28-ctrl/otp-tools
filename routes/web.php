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

use App\Http\Controllers\UserDashboardController;

Route::get('/app/login', [UserDashboardController::class, 'showLogin'])->name('login');
Route::post('/app/login', [UserDashboardController::class, 'login']);
Route::post('/app/logout', [UserDashboardController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/app', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('/app/generate', [UserDashboardController::class, 'showGenerate'])->name('generate.page');
    Route::get('/app/numbers', [UserDashboardController::class, 'showNumbers'])->name('numbers.page');
    Route::post('/app/generate', [UserDashboardController::class, 'generate'])->name('generate.number');
    Route::get('/app/countries/active', [UserDashboardController::class, 'getActiveCountries'])->name('countries.active');
    Route::get('/app/sms/poll', [UserDashboardController::class, 'pollActiveSms'])->name('sms.poll');
    Route::get('/app/sms/{id}', [UserDashboardController::class, 'checkSms'])->name('check.sms');
});
