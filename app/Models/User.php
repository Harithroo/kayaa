<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'is_admin'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The last-admin rule lives here, not in the UI, so no screen, command or
        // script can lock everyone out of /admin. A refused delete or demotion
        // returns false and changes nothing.
        static::deleting(fn (User $user) => $user->canBeDeleted());

        static::updating(function (User $user) {
            $demoted = $user->isDirty('is_admin') && ! $user->is_admin && $user->getOriginal('is_admin');

            return ! $demoted || self::otherAdminsExist($user);
        });
    }

    /** True when at least one admin other than $user exists. */
    private static function otherAdminsExist(User $user): bool
    {
        return self::where('is_admin', true)->whereKeyNot($user->getKey())->exists();
    }

    /**
     * Only admins are restricted here (the Customers screen has its own rule
     * about accounts with orders): an admin cannot delete themselves, and the
     * last admin cannot be deleted by anyone.
     */
    public function canBeDeleted(): bool
    {
        if (! $this->is_admin) {
            return true;
        }

        return auth()->id() !== $this->id && self::otherAdminsExist($this);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Orders that belong to this customer. Ownership means user_id, full stop.
     * Guest orders are attached to the account at sign-in (and only when both the
     * email and the phone number match) — a matching email on its own is not
     * proof of anything, since nothing stops someone signing up with it.
     */
    public function allOrders(): Builder
    {
        return Order::query()->where('user_id', $this->id);
    }

    /**
     * Guest orders this account could take over: no owner yet, and both the email
     * and the phone number match.
     *
     * Both have to match. Email alone is not enough: anyone can register with an
     * address they don't own, and claiming on email alone would hand them that
     * person's orders, delivery address and phone number. An account with no
     * phone number on it claims nothing.
     */
    public function claimableGuestOrders(): Builder
    {
        $orders = Order::query()->whereNull('user_id');

        if (blank($this->phone) || blank($this->email)) {
            return $orders->whereRaw('1 = 0');
        }

        return $orders->where('email', $this->email)->where('phone', $this->phone);
    }

    /** Attach those orders to this account. Returns how many were linked. */
    public function claimGuestOrders(): int
    {
        return $this->claimableGuestOrders()->update(['user_id' => $this->id]);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** True when this customer has an order in progress or delivered containing the product. */
    public function hasPurchased(Product $product): bool
    {
        return $this->allOrders()
            ->whereIn('status', ['confirmed', 'shipped', 'delivered'])
            ->whereHas('items.variant', fn ($q) => $q->where('product_id', $product->id))
            ->exists();
    }
}
