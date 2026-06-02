<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use Illuminate\Support\Facades\Artisan;
Route::get('/cron/run-scheduler', function () {
    Artisan::call('schedule:run');
    return response()->json(['status' => 'Scheduler executed']);
});
