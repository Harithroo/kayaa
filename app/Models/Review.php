<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'order_id', 'name', 'email', 'rating',
        'title', 'body', 'is_verified_purchase', 'is_approved', 'approved_at', 'admin_reply',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Only approved reviews are ever shown on the storefront. */
    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('is_approved', true);
    }

    public function approve(): void
    {
        $this->forceFill(['is_approved' => true, 'approved_at' => now()])->save();
    }

    public function unapprove(): void
    {
        $this->forceFill(['is_approved' => false, 'approved_at' => null])->save();
    }

    /** "Nimali P." — surnames are trimmed to an initial on the storefront. */
    public function displayName(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        if (count($parts) < 2) {
            return $this->name;
        }

        return $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.';
    }
}
