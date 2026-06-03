<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Country;
use App\Models\PhoneNumber;
use App\Services\ProviderInterface;

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
        $numbers = PhoneNumber::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return view('user.dashboard', compact('numbers'));
    }

    public function showGenerate()
    {
        $countries = Country::where('status', true)->get();
        return view('user.generate', compact('countries'));
    }

    public function showNumbers()
    {
        $numbers = PhoneNumber::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return view('user.numbers', compact('numbers'));
    }

    public function generate(Request $request, ProviderInterface $apiService)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'service' => 'required|string',
        ]);

        $country = Country::find($validated['country_id']);
        
        try {
            $response = $apiService->getNumberByCountry($country->code);
            
            // The RapidAPI returns a flat array of numbers: ["7348624600"]
            if (isset($response['success']) && $response['success'] == true && !empty($response['data'])) {
                $numbersList = $response['data'];
                $selectedNumber = null;
                $serviceName = strtolower($validated['service']);

                foreach ($numbersList as $phoneNumberValue) {
                    // Prepend country code if the number does not already start with it
                    $fullPhoneNumber = $phoneNumberValue;
                    if (!str_starts_with($phoneNumberValue, $country->code)) {
                        $fullPhoneNumber = $country->code . $phoneNumberValue;
                    }

                    // Pre-scan SMS history for the selected service
                    $isUsedExceeded = false;
                    $historyResponse = $apiService->checkSmsHistory($country->code, $fullPhoneNumber);
                    
                    if (isset($historyResponse['success']) && $historyResponse['success'] == true && !empty($historyResponse['data'])) {
                        $msgCount = 0;
                        
                        // Define keywords for the selected service to filter SMS history
                        $keywords = [$serviceName];
                        if ($serviceName === 'facebook' || $serviceName === 'instagram') {
                            $keywords = ['facebook', 'instagram', 'meta', 'fb', 'ig'];
                        } elseif ($serviceName === 'google') {
                            $keywords = ['google', 'gmail', 'g-'];
                        } elseif ($serviceName === 'whatsapp') {
                            $keywords = ['whatsapp', 'wa'];
                        } elseif ($serviceName === 'telegram') {
                            $keywords = ['telegram', 'tg'];
                        } elseif ($serviceName === 'twitter') {
                            $keywords = ['twitter', 'x.com', 'x '];
                        }

                        foreach ($historyResponse['data'] as $msg) {
                            $text = strtolower($msg['text'] ?? '');
                            $from = strtolower($msg['from'] ?? '');
                            
                            foreach ($keywords as $kw) {
                                if (str_contains($text, $kw) || str_contains($from, $kw)) {
                                    $msgCount++;
                                    break; // Count once per message
                                }
                            }
                        }

                        // Reject if used more than 2 times (3 or more) for the selected platform
                        if ($msgCount >= 3) {
                            $isUsedExceeded = true;
                        }
                    }

                    if (!$isUsedExceeded) {
                        $selectedNumber = $fullPhoneNumber;
                        break; // Found a good, fresh number!
                    }
                }

                if ($selectedNumber === null) {
                    return back()->with('error', 'All available numbers for ' . $country->name . ' have already been used/verified for ' . ucfirst($validated['service']) . '. Please try another country.');
                }

                $number = PhoneNumber::create([
                    'user_id' => Auth::id(),
                    'country_id' => $country->id,
                    'number' => $selectedNumber,
                    'service' => $validated['service'],
                    'provider_order_id' => $response['data']['order_id'] ?? null,
                    'status' => 'active',
                ]);

                // Ensure the country is marked as active in stock
                if (!$country->status) {
                    $country->update(['status' => true]);
                }
                
                return back()->with('success', 'Number generated and pre-scanned successfully!')
                             ->with('generated_number', $selectedNumber)
                             ->with('number_id', $number->id);
            } else {
                // Out of stock or failed API response.
                // Do NOT disable the country if it was a rate limit / quota exceeded error (429)
                $isRateLimit = isset($response['message']) && str_contains($response['message'], '429');
                if (!$isRateLimit) {
                    $country->update(['status' => false]);
                }

                $errorMessage = 'Selected country is currently out of stock. Please select another country.';
                if (isset($response['message']) && !empty($response['message'])) {
                    $errorMessage = $response['message'];
                }

                return back()->with('error', $errorMessage);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Connection Error: ' . $e->getMessage());
        }
    }

    public function discard($id)
    {
        try {
            $number = PhoneNumber::where('user_id', Auth::id())->findOrFail($id);
            $number->delete();
            return back()->with('success', 'Number discarded successfully from inventory.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function getActiveCountries()
    {
        $countries = Country::where('status', true)->get(['id', 'name', 'code']);
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
            
            // RapidAPI checkSmsHistory returns: [ 'success' => true, 'data' => [ [ 'text' => '...', 'from' => '...', 'createdAt' => '...' ] ] ]
            if (isset($response['success']) && $response['success'] == true && !empty($response['data'])) {
                $latestSms = $response['data'][0]; // Select the latest message from the array
                
                // Attempt to parse out a 4-to-8 digit OTP from the SMS text
                $otp = null;
                if (preg_match('/\b\d{4,8}\b/', $latestSms['text'], $matches)) {
                    $otp = $matches[0];
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
