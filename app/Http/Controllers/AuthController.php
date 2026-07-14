<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $minBirthDate = now()->subYears(70)->toDateString();
        $maxBirthDate = now()->subYears(18)->toDateString();

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:profiles,email'],
            'phone_number' => ['required', 'string', 'max:30'],
            'birth_date' => [
                'required',
                'date',
                'after_or_equal:' . $minBirthDate,
                'before_or_equal:' . $maxBirthDate,
            ],
            'password' => ['required', 'min:6', 'confirmed'],
        ], [
            'birth_date.after_or_equal' => 'You must be 70 years old or below to register.',
            'birth_date.before_or_equal' => 'You must be at least 18 years old to register.',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create([
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'birth_date' => $validated['birth_date'],
            ]);

            return $user;
        });

        Auth::login($user);

        return redirect('/dashboard')->with('show_investment_notice', true);
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect('/dashboard')->with('show_investment_notice', true);
        }

        return back()->withErrors([
            'username' => 'Invalid username or password.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}