<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\SessionGuard;
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

        Auth::login($user);
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

        $this->capRememberDuration();

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

    /**
     * Attach past guest orders to this account.
     *
     * Both the email and the phone number have to match. Email alone is not
     * enough: anyone can register with an address they don't own, and claiming
     * on email alone would hand them that person's orders, delivery address and
     * phone number. An account with no phone number on it claims nothing.
     */
    private function claimGuestOrders(User $user): void
    {
        if (blank($user->phone)) {
            return;
        }

        Order::whereNull('user_id')
            ->where('email', $user->email)
            ->where('phone', $user->phone)
            ->update(['user_id' => $user->id]);
    }

    /**
     * "Keep me signed in" lasts 30 days. Laravel's default remember cookie has
     * a five-year lifetime, which is too long for a device that might be shared.
     */
    private function capRememberDuration(): void
    {
        $guard = Auth::guard('web');

        if ($guard instanceof SessionGuard) {
            $guard->setRememberDuration(60 * 24 * 30);
        }
    }
}
