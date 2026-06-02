<?php

namespace App\Jobs;

use App\Models\Country;
use App\Services\ProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchCountriesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(ProviderInterface $provider): void
    {
        try {
            $countries = $provider->getCountries();

            if (empty($countries)) {
                Log::warning('FetchCountriesJob: No countries returned from provider.');
                return;
            }

            foreach ($countries as $countryData) {
                // Adjust based on the actual response structure of ZylaLabs API
                if (isset($countryData['code']) && isset($countryData['name'])) {
                    Country::updateOrCreate(
                        ['code' => $countryData['code']],
                        ['name' => $countryData['name'], 'status' => true]
                    );
                }
            }

            Log::info('FetchCountriesJob: Successfully synced countries.');
        } catch (\Exception $e) {
            Log::error('FetchCountriesJob Exception: ' . $e->getMessage());
        }
    }
}
