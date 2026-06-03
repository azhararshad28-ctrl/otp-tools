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
            
            // The Zyla API returns: {"status": 200, "success": true, "message": "", "data": ["number1", "number2"]}
            if (isset($response['success']) && $response['success'] == true && !empty($response['data'])) {
                // Zyla returns a flat list of available numbers. We select the first one.
                $phoneNumberValue = $response['data'][0];

                $number = PhoneNumber::create([
                    'user_id' => Auth::id(),
                    'country_id' => $country->id,
                    'number' => $phoneNumberValue,
                    'service' => $validated['service'],
                    'provider_order_id' => $response['data']['order_id'] ?? null, // Zyla may not have order_id in this endpoint
                    'status' => 'active',
                ]);

                // Ensure the country is marked as active in stock
                if (!$country->status) {
                    $country->update(['status' => true]);
                }
                
                return back()->with('success', 'Number generated successfully!')
                             ->with('generated_number', $phoneNumberValue)
                             ->with('number_id', $number->id);
            } else {
                // Out of stock or failed API response.
                // Mark country as out of stock so it is temporarily hidden from frontend dropdown
                $country->update(['status' => false]);

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
        $numbers = PhoneNumber::with('country')->where('user_id', Auth::id())->where('status', 'active')->get();
        $results = [];

        foreach ($numbers as $number) {
            try {
                $response = $apiService->checkSmsHistory($number->country->code ?? 'US', $number->number);
                
                if (isset($response['success']) && $response['success'] == true && !empty($response['data'])) {
                    // Extract latest SMS
                    $latestSms = $response['data'][0]; // This is an array with ['from', 'text', 'myNumber', 'createdAt']
                    
                    // Parse out a potential OTP (4 to 8 digits)
                    $otp = null;
                    if (preg_match('/\b\d{4,8}\b/', $latestSms['text'], $matches)) {
                        $otp = $matches[0];
                    }
                    
                    $results[$number->id] = [
                        'has_sms' => true,
                        'text' => $latestSms['text'],
                        'otp' => $otp,
                        'from' => $latestSms['from'],
                        'time' => $latestSms['createdAt'] ?? 'just now'
                    ];
                } else {
                    $results[$number->id] = [
                        'has_sms' => false,
                        'message' => 'Waiting for SMS...'
                    ];
                }
            } catch (\Exception $e) {
                $results[$number->id] = [
                    'has_sms' => false,
                    'message' => 'Connection error'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'sms_data' => $results
        ]);
    }

    public function checkSms($id, ProviderInterface $apiService)
    {
        $number = PhoneNumber::with('country')->where('user_id', Auth::id())->findOrFail($id);
        
        try {
            $response = $apiService->checkSmsHistory($number->country->code ?? 'US', $number->number);
            
            if (isset($response['success']) && $response['success'] == true) {
                return response()->json([
                    'success' => true,
                    'sms' => $response['data']['sms'] ?? 'No SMS content yet'
                ]);
            } elseif (isset($response['sms'])) {
                 return response()->json([
                    'success' => true,
                    'sms' => $response['sms']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? (is_array($response) ? json_encode($response) : 'Please wait and try again.')
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
