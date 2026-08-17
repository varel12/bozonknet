<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'status' => 'active',
        ], $remember)) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak sesuai, atau akun belum aktif.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectByRole();
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda sudah logout.');
    }

    private function redirectByRole(): RedirectResponse
    {
        $role = Auth::user()?->role;

        return redirect()->intended(
            $role === 'teknisi'
                ? route('internal.teknisi')
                : route('internal.admin')
        );
    }
}
