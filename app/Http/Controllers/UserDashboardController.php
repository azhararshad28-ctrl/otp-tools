<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Country;
use App\Models\PhoneNumber;
use App\Models\SystemAuditLog;
use App\Services\ProviderInterface;
use App\Services\NumberRotationService;

class UserDashboardController extends Controller
{
    public function showLogin()
    {
        return view('user.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/app');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/app/login');
    }

    public function index()
    {
        $numbers = PhoneNumber::with('country')
            ->where('user_id', Auth::id())
            ->where('status', '!=', 'discarded')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate analytics
        $totalNumbers = PhoneNumber::where('user_id', Auth::id())->count();
        $activeCount = PhoneNumber::where('user_id', Auth::id())->where('status', 'active')->count();
        
        $totalSuccesses = PhoneNumber::where('user_id', Auth::id())->sum('success_count');
        $totalFailures = PhoneNumber::where('user_id', Auth::id())->sum('fail_count');
        $totalAttempts = $totalSuccesses + $totalFailures;
        
        $successRate = $totalAttempts > 0 ? round(($totalSuccesses / $totalAttempts) * 100, 1) : 100.0;
        $avgHealthScore = round(PhoneNumber::where('user_id', Auth::id())->avg('reputation_score') ?? 100.0, 1);

        // Fetch country-wise stats
        $countryStats = Country::where('status', true)->get()->map(function ($country) {
            $successes = PhoneNumber::where('country_id', $country->id)->sum('success_count');
            $failures = PhoneNumber::where('country_id', $country->id)->sum('fail_count');
            $total = $successes + $failures;
            $rate = $total > 0 ? round(($successes / $total) * 100) : 100;
            $totalNumbers = PhoneNumber::where('country_id', $country->id)->count();
            return [
                'name' => $country->name,
                'code' => $country->code,
                'total' => $totalNumbers,
                'rate' => $rate
            ];
        })->sortByDesc('total')->take(5)->values()->toArray();

        // Fetch latest audit logs for the dashboard
        $auditLogs = SystemAuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('user.dashboard', compact(
            'numbers',
            'totalNumbers',
            'activeCount',
            'successRate',
            'avgHealthScore',
            'countryStats',
            'auditLogs'
        ));
    }

    public function showGenerate()
    {
        $countries = \Illuminate\Support\Facades\Cache::remember('countries_active_list_v1', 600, function () {
            return Country::where('status', true)->get();
        });
        return view('user.generate', compact('countries'));
    }

    public function showNumbers()
    {
        $numbers = PhoneNumber::with('country')
            ->where('user_id', Auth::id())
            ->where('status', '!=', 'discarded')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('user.numbers', compact('numbers'));
    }

    public function generate(Request $request, ProviderInterface $apiService)
    {
        $validated = $request->validate([
            'country_id' => 'required|string',
            'service' => 'required|string',
        ]);

        if ($validated['country_id'] !== 'any') {
            $request->validate([
                'country_id' => 'exists:countries,id'
            ]);

            $country = Country::find($validated['country_id']);
            
            try {
                $response = $apiService->getNumberByCountry($country->code);
                
                if (isset($response['success']) && $response['success'] == true && !empty($response['data'])) {
                    $numbersList = $response['data'];
                    $rotationService = new NumberRotationService();
                    
                    // Select the best fresh or high-reputation number from the list
                    $selectedNumber = $rotationService->selectBestNumber($numbersList, $country->id, $validated['service']);

                    if ($selectedNumber === null) {
                        NumberRotationService::logEvent('GENERATION_BLOCKED', "All numbers in {$country->name} are blocked or used for " . ucfirst($validated['service']));
                        return back()->with('error', 'All available numbers for ' . $country->name . ' have already been used/verified for ' . ucfirst($validated['service']) . '. Please try another country.');
                    }

                    // Prepend country code if needed
                    $fullPhoneNumber = $selectedNumber;
                    if (!str_starts_with($selectedNumber, $country->code)) {
                        $fullPhoneNumber = $country->code . $selectedNumber;
                    }

                    $number = PhoneNumber::create([
                        'user_id' => Auth::id(),
                        'country_id' => $country->id,
                        'number' => $fullPhoneNumber,
                        'service' => $validated['service'],
                        'provider_order_id' => $response['data']['order_id'] ?? null,
                        'status' => 'active',
                        'last_used_at' => now(),
                    ]);

                    // Ensure the country is marked as active in stock
                    if (!$country->status) {
                        $country->update(['status' => true]);
                        \Illuminate\Support\Facades\Cache::forget('countries_active_list_v1');
                        \Illuminate\Support\Facades\Cache::forget('countries_active_api_v1');
                    }

                    NumberRotationService::logEvent('NUMBER_ACQUIRED', "Acquired number +{$fullPhoneNumber} for " . ucfirst($validated['service']) . " ({$country->name})");
                    
                    return back()->with('success', 'Number generated and pre-scanned successfully!')
                                 ->with('generated_number', $fullPhoneNumber)
                                 ->with('generated_country_name', $country->name)
                                 ->with('number_id', $number->id);
                } else {
                    // Out of stock or failed API response.
                    $isRateLimit = isset($response['message']) && str_contains($response['message'], '429');
                    if (!$isRateLimit) {
                        $country->update(['status' => false]);
                        \Illuminate\Support\Facades\Cache::forget('countries_active_list_v1');
                        \Illuminate\Support\Facades\Cache::forget('countries_active_api_v1');
                    }

                    $errorMessage = 'Selected country is currently out of stock. Please select another country.';
                    if (isset($response['message']) && !empty($response['message'])) {
                        $errorMessage = $response['message'];
                    }

                    NumberRotationService::logEvent('GENERATION_OUT_OF_STOCK', "Generation failed for {$country->name} ({$validated['service']}): {$errorMessage}");

                    return back()->with('error', $errorMessage);
                }
            } catch (\Exception $e) {
                NumberRotationService::logEvent('GENERATION_ERROR', "Exception: " . $e->getMessage());
                return back()->with('error', 'Connection Error: ' . $e->getMessage());
            }
        } else {
            // Mix Mode - Get best number from any active country
            try {
                $countries = Country::where('status', true)->get();
                $candidates = [];

                foreach ($countries as $c) {
                    $response = $apiService->getNumberByCountry($c->code);
                    if (isset($response['success']) && $response['success'] == true && !empty($response['data'])) {
                        foreach ($response['data'] as $num) {
                            $candidates[] = [
                                'number' => $num,
                                'country_id' => $c->id,
                                'country_code' => $c->code,
                                'country_name' => $c->name
                            ];
                        }
                    }
                }

                if (empty($candidates)) {
                    NumberRotationService::logEvent('GENERATION_OUT_OF_STOCK', "Generation failed in Mix Mode: No numbers in stock across all countries.");
                    return back()->with('error', 'All countries are currently out of stock. Please try again later.');
                }

                $rotationService = new NumberRotationService();
                $bestCandidate = $rotationService->selectBestMixedNumber($candidates, $validated['service']);

                if ($bestCandidate === null) {
                    NumberRotationService::logEvent('GENERATION_BLOCKED', "All available numbers across all countries are blocked or used for " . ucfirst($validated['service']));
                    return back()->with('error', 'All numbers across all countries have already been used/verified for ' . ucfirst($validated['service']) . '.');
                }

                $selectedNumber = $bestCandidate['number'];
                $countryId = $bestCandidate['country_id'];
                $countryCode = $bestCandidate['country_code'];
                $countryName = $bestCandidate['country_name'];

                // Prepend country code if needed
                $fullPhoneNumber = $selectedNumber;
                if (!str_starts_with($selectedNumber, $countryCode)) {
                    $fullPhoneNumber = $countryCode . $selectedNumber;
                }

                $number = PhoneNumber::create([
                    'user_id' => Auth::id(),
                    'country_id' => $countryId,
                    'number' => $fullPhoneNumber,
                    'service' => $validated['service'],
                    'status' => 'active',
                    'last_used_at' => now(),
                ]);

                NumberRotationService::logEvent('NUMBER_ACQUIRED', "Acquired number +{$fullPhoneNumber} for " . ucfirst($validated['service']) . " (Mix Mode - Selected: {$countryName})");
                
                return back()->with('success', 'Number generated and pre-scanned successfully!')
                             ->with('generated_number', $fullPhoneNumber)
                             ->with('generated_country_name', $countryName)
                             ->with('number_id', $number->id);

            } catch (\Exception $e) {
                NumberRotationService::logEvent('GENERATION_ERROR', "Mix Mode Exception: " . $e->getMessage());
                return back()->with('error', 'Connection Error: ' . $e->getMessage());
            }
        }
    }

    public function discard($id)
    {
        try {
            $number = PhoneNumber::where('user_id', Auth::id())->findOrFail($id);
            
            $rotationService = new NumberRotationService();
            $rotationService->recordDiscard($number->id);
            
            // Mark as discarded in local view instead of fully deleting to preserve logs
            $number->update(['status' => 'discarded']);
            
            return back()->with('success', 'Number discarded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function getActiveCountries()
    {
        $countries = \Illuminate\Support\Facades\Cache::remember('countries_active_api_v1', 600, function () {
            return Country::where('status', true)->get(['id', 'name', 'code']);
        });
        return response()->json([
            'success' => true,
            'countries' => $countries
        ]);
    }

    public function pollActiveSms(ProviderInterface $apiService)
    {
        // Keep this method if needed in the future, return empty response for now to save quota
        return response()->json([
            'success' => true,
            'sms_data' => []
        ]);
    }

    public function checkSms($id, ProviderInterface $apiService)
    {
        $number = PhoneNumber::with('country')->where('user_id', Auth::id())->findOrFail($id);
        
        try {
            $response = $apiService->checkSmsHistory($number->country->code ?? 'US', $number->number);
            
            if (isset($response['success']) && $response['success'] == true && !empty($response['data'])) {
                $latestSms = $response['data'][0]; // Select the latest message
                
                // Attempt to parse out a 4-to-8 digit OTP from the SMS text
                $otp = null;
                if (preg_match('/\b\d{4,8}\b/', $latestSms['text'], $matches)) {
                    $otp = $matches[0];
                }
                
                // Successfully received SMS - close number and record success
                if ($number->status === 'active') {
                    $number->update(['status' => 'closed']);
                    $rotationService = new NumberRotationService();
                    $rotationService->recordSuccess($number->id);
                }
                
                return response()->json([
                    'success' => true,
                    'has_sms' => true,
                    'sms' => $latestSms['text'],
                    'otp' => $otp,
                    'from' => $latestSms['from'],
                    'time' => $latestSms['createdAt'] ?? 'just now'
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'has_sms' => false,
                    'message' => 'No SMS received yet. Click Fetch OTP again after requesting the code.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection Error: ' . $e->getMessage()
            ]);
        }
    }
}
