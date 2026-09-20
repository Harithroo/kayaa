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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Orders that belong to this customer: the ones placed while signed in, plus any
     * guest order placed with the same email address before they had an account.
     */
    public function allOrders(): Builder
    {
        return Order::query()
            ->where(fn ($q) => $q->where('user_id', $this->id)->orWhere('email', $this->email));
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
