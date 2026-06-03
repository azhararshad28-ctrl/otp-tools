<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Country;
use App\Services\ProviderInterface;
use Illuminate\Support\Facades\Log;

class ScanCountriesStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scan-countries-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan RapidAPI for available country stock and update status';

    /**
     * Execute the console command.
     */
    public function handle(ProviderInterface $provider): void
    {
        $this->info('Starting country stock scan...');
        $countries = Country::all();

        foreach ($countries as $country) {
            try {
                $response = $provider->getNumberByCountry($country->code);
                
                // RapidAPI returns {"status": 200, "success": true, "message": "", "data": [...]}
                // If data is empty or empty array, it means it is out of stock.
                if (isset($response['success']) && $response['success'] === true && !empty($response['data'])) {
                    $country->status = true;
                    $this->info("Country {$country->name} ({$country->code}): IN STOCK (" . count($response['data']) . " numbers)");
                } elseif (isset($response['data']) && !empty($response['data'])) {
                    $country->status = true;
                    $this->info("Country {$country->name} ({$country->code}): IN STOCK (fallback)");
                } else {
                    $country->status = false;
                    $this->warn("Country {$country->name} ({$country->code}): OUT OF STOCK");
                }
                
                $country->save();
            } catch (\Exception $e) {
                $this->error("Failed to scan {$country->name} ({$country->code}): " . $e->getMessage());
                Log::error('ScanCountriesStock command failed for country ' . $country->code, ['message' => $e->getMessage()]);
            }
        }

        // Evict cached lists since statuses have been updated
        \Illuminate\Support\Facades\Cache::forget('active_countries');
        \Illuminate\Support\Facades\Cache::forget('active_countries_api');

        $this->info('Country stock scan completed!');
    }
}
