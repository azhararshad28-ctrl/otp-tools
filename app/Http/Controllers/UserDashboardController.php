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
        $countries = Country::where('status', true)->get();
        $numbers = PhoneNumber::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return view('user.dashboard', compact('countries', 'numbers'));
    }

    public function generate(Request $request, ProviderInterface $apiService)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'service' => 'required|string',
        ]);

        $country = Country::find($validated['country_id']);
        
        try {
            $response = $apiService->generateNumber($country->code, $validated['service']);
            
            if ($response['success']) {
                PhoneNumber::create([
                    'user_id' => Auth::id(),
                    'country_id' => $country->id,
                    'phone_number' => $response['data']['phone_number'] ?? 'N/A',
                    'service' => $validated['service'],
                    'provider_order_id' => $response['data']['order_id'] ?? null,
                    'status' => 'active',
                ]);
                
                return back()->with('success', 'Number generated successfully!');
            } else {
                return back()->with('error', $response['message'] ?? 'Unknown API error');
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function checkSms($id, ProviderInterface $apiService)
    {
        $number = PhoneNumber::where('user_id', Auth::id())->findOrFail($id);
        
        try {
            $response = $apiService->getSms($number->provider_order_id ?? $number->number);
            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'sms' => $response['data']['sms'] ?? 'No SMS content yet'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Please wait and try again.'
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
