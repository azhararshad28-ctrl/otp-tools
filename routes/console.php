<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Jobs\FetchCountriesJob;
use App\Models\PhoneNumber;
use App\Jobs\CheckSmsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Refresh countries daily
Schedule::job(new FetchCountriesJob)->daily();

// Automatically run Check SMS every minute for all active phone numbers
Schedule::call(function () {
    $activeNumbers = PhoneNumber::where('status', 'active')->get();
    foreach ($activeNumbers as $number) {
        CheckSmsJob::dispatch($number->id);
    }
})->everyMinute();

// Scan RapidAPI country stock every 10 minutes
Schedule::command('app:scan-countries-stock')->everyTenMinutes();

