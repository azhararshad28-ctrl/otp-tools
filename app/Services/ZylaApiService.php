<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZylaApiService implements ProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        // Settings are dynamic
        $this->baseUrl = Setting::where('key', 'api_base_url')->value('value') ?? 'https://zylalabs.com/api/1813/virtual+phone+number+generator+api';
        $this->apiKey = Setting::where('key', 'api_key')->value('value') ?? '';
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ];
    }

    public function getCountries(): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/1466/get+countries");
            
            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Zyla API getCountries failed', ['status' => $response->status(), 'response' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('Zyla API getCountries exception', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getNumberByCountry(string $countryCode): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/1467/get+number+by+country+id", [
                    'countryCode' => $countryCode
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Zyla API getNumberByCountry failed', ['status' => $response->status(), 'response' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('Zyla API getNumberByCountry exception', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function checkSmsHistory(string $countryCode, string $phoneNumber): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/1469/check+sms+history", [
                    'countryCode' => $countryCode,
                    'phoneNumber' => $phoneNumber
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Zyla API checkSmsHistory failed', ['status' => $response->status(), 'response' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('Zyla API checkSmsHistory exception', ['message' => $e->getMessage()]);
            return [];
        }
    }
}
