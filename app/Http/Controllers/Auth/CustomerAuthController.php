<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Storefront accounts. Customers live in the same users table as admins with
 * is_admin = false, so the Filament panel stays closed to them (canAccessPanel).
 * Accounts are optional throughout — guest checkout and /track still work.
 */
class CustomerAuthController extends Controller
{
    public function showRegister(): View
    {
        return view('store.account.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^0?7\d[\s-]?\d{3}[\s-]?\d{4}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => 'Enter a Sri Lankan mobile number, e.g. 077 123 4567.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => isset($data['phone']) ? preg_replace('/\D+/', '', $data['phone']) : null,
            'password' => $data['password'],
            'is_admin' => false,
        ]);

        $this->claimGuestOrders($user);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('account.index')->with('success', 'Account created. Welcome to Kayaa.');
    }

    public function showLogin(): View
    {
        return view('store.account.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Those details don\'t match an account.']);
        }

        $request->session()->regenerate();
        $this->claimGuestOrders($request->user());

        return redirect()->intended(route('account.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Signed out.');
    }

    /** Attach past guest orders placed with the same email to the new account. */
    private function claimGuestOrders(User $user): void
    {
        Order::whereNull('user_id')->where('email', $user->email)->update(['user_id' => $user->id]);
    }
}
