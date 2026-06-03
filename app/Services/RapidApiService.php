<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RapidApiService implements ProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        // Settings are dynamic
        $this->baseUrl = Setting::where('key', 'api_base_url')->value('value') ?? 'https://virtual-number.p.rapidapi.com/api/v1/e-sim';
        $this->apiKey = Setting::where('key', 'api_key')->value('value') ?? '5228aeb52fmsh36211b8b7debdcap160432jsn3f823bebfb35';
    }

    protected function getHeaders(): array
    {
        return [
            'x-rapidapi-key' => $this->apiKey,
            'x-rapidapi-host' => parse_url($this->baseUrl, PHP_URL_HOST) ?? 'virtual-number.p.rapidapi.com',
            'Accept' => 'application/json',
        ];
    }

    public function getCountries(): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/all-countries");
            
            if ($response->successful()) {
                $data = [];
                foreach ($response->json() as $item) {
                    if (isset($item['countryCode']) && isset($item['countryName'])) {
                        $data[] = [
                            'code' => $item['countryCode'],
                            'name' => ucwords(str_replace('_', ' ', $item['countryName']))
                        ];
                    }
                }
                return [
                    'success' => true,
                    'data' => $data
                ];
            }

            Log::error('RapidAPI getCountries failed', ['status' => $response->status(), 'response' => $response->body()]);
            return ['success' => false, 'message' => 'API Error ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('RapidAPI getCountries exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Connection Error: ' . $e->getMessage()];
        }
    }

    public function getNumberByCountry(string $countryCode): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/country-numbers", [
                    'countryId' => $countryCode
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('RapidAPI getNumberByCountry failed', ['status' => $response->status(), 'response' => $response->body()]);
            return ['success' => false, 'message' => 'API Error ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('RapidAPI getNumberByCountry exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Connection Error: ' . $e->getMessage()];
        }
    }

    public function checkSmsHistory(string $countryCode, string $phoneNumber): array
    {
        try {
            // Strip country code prefix if present, as the API expects the local number
            if (str_starts_with($phoneNumber, $countryCode)) {
                $phoneNumber = substr($phoneNumber, strlen($countryCode));
            }

            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/view-messages", [
                    'countryId' => $countryCode,
                    'number' => $phoneNumber
                ]);

            if ($response->successful()) {
                $json = $response->json();
                
                // Map RapidAPI list format to standard return format for our controller
                // RapidAPI returns directly: [ { "text": "...", "serviceName": "...", "myNumber": "...", "createdAt": "..." } ]
                $mappedData = [];
                foreach ($json as $msg) {
                    $mappedData[] = [
                        'from' => $msg['serviceName'] ?? 'Unknown',
                        'text' => $msg['text'] ?? '',
                        'myNumber' => $msg['myNumber'] ?? $phoneNumber,
                        'createdAt' => $msg['createdAt'] ?? 'just now'
                    ];
                }

                return [
                    'success' => true,
                    'data' => $mappedData
                ];
            }

            Log::error('RapidAPI checkSmsHistory failed', ['status' => $response->status(), 'response' => $response->body()]);
            return ['success' => false, 'message' => 'API Error ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('RapidAPI checkSmsHistory exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Connection Error: ' . $e->getMessage()];
        }
    }
}
