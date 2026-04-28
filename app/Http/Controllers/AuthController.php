<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // Show login form
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Handle login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        // dd($credentials);
        // print_r($request);
        // die();
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:4',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
           // dd($user);
            // Redirect based on role
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user->role === 'provider') {
                return redirect()->route('provider.dashboard');
            }

            // Default fallback
            Auth::logout();
            return redirect()->route('login')->withErrors(['role' => 'Unauthorized access.']);
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }


    public function showRegisterForm()
{
    return view('auth.register');
}

public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'phone' => 'required|string|max:20',
        'password' => 'required|string|min:6|confirmed',
        'role' => 'required|in:admin,provider',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'password' => Hash::make($request->password),
        'role' => $request->role,
    ]);

    return redirect()->route('login')->with('success', 'User created successfully! You can now log in.');
}
}
