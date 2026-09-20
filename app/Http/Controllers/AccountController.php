<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->allOrders()
            ->with('items')
            ->latest()
            ->paginate(10);

        return view('store.account.index', compact('orders'));
    }

    public function order(Request $request, Order $order): View
    {
        abort_unless($this->owns($request, $order), 404);
        $order->load('items');

        return view('store.account.order', compact('order'));
    }

    public function reviews(Request $request): View
    {
        $reviews = $request->user()->reviews()->with('product')->latest()->get();

        return view('store.account.reviews', compact('reviews'));
    }

    public function profile(Request $request): View
    {
        return view('store.account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'regex:/^0?7\d[\s-]?\d{3}[\s-]?\d{4}$/'],
        ], [
            'phone.regex' => 'Enter a Sri Lankan mobile number, e.g. 077 123 4567.',
        ]);

        $data['phone'] = isset($data['phone']) ? preg_replace('/\D+/', '', $data['phone']) : null;
        $user->update($data);

        return back()->with('success', 'Details saved.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'That is not your current password.']);
        }

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password changed.');
    }

    /** An order belongs to the customer via user_id, or by matching their email. */
    private function owns(Request $request, Order $order): bool
    {
        $user = $request->user();

        return $order->user_id === $user->id
            || ($order->email !== null && strcasecmp($order->email, $user->email) === 0);
    }
}
