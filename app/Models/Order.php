<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    protected $fillable = [
        'user_id', 'reference', 'status', 'payment_method', 'payment_status', 'payment_reference',
        'first_name', 'last_name', 'phone', 'email', 'address', 'city', 'district', 'note',
        'subtotal', 'shipping', 'total', 'paid_at', 'shipped_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'shipping' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customerName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function markPaid(?string $paymentReference = null): void
    {
        $this->forceFill([
            'payment_status' => 'paid',
            'payment_reference' => $paymentReference ?? $this->payment_reference,
            'paid_at' => now(),
            'status' => $this->status === 'pending' ? 'confirmed' : $this->status,
        ])->save();
    }

    /** KY-240917-A3F9 — date prefix keeps them roughly sortable by eye. */
    public static function generateReference(): string
    {
        do {
            $ref = 'KY-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }
}
