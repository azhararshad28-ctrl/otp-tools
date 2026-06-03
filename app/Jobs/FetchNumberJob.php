<?php

namespace App\Jobs;

use App\Models\Country;
use App\Models\PhoneNumber;
use App\Services\ProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchNumberJob implements ShouldQueue
{
    use Queueable;

    protected string $countryCode;

    /**
     * Create a new job instance.
     */
    public function __construct(string $countryCode)
    {
        $this->countryCode = $countryCode;
    }

    /**
     * Execute the job.
     */
    public function handle(ProviderInterface $provider): void
    {
        try {
            $response = $provider->getNumberByCountry($this->countryCode);

            if (empty($response)) {
                Log::warning("FetchNumberJob: No number returned for country {$this->countryCode}");
                return;
            }

            // Assume the API returns something like ['number' => '+1234567890']
            if (isset($response['number'])) {
                $country = Country::where('code', $this->countryCode)->first();
                
                if ($country) {
                    PhoneNumber::updateOrCreate(
                        ['number' => $response['number']],
                        [
                            'country_id' => $country->id,
                            'provider' => 'RapidAPI',
                            'status' => 'active',
                            'last_checked' => now(),
                        ]
                    );
                    Log::info("FetchNumberJob: Added number {$response['number']} for {$this->countryCode}");
                }
            }
        } catch (\Exception $e) {
            Log::error("FetchNumberJob Exception for {$this->countryCode}: " . $e->getMessage());
        }
    }
}
