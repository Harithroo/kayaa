<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    /** A shopper cancelling is an order status, not a payment status. */
    public const PAYMENT_METHODS = ['cod', 'onepay'];

    /** How long a checkout link stays valid for the customer who placed the order. */
    public const LINK_TTL_DAYS = 30;

    protected $fillable = [
        'user_id', 'reference', 'status', 'payment_method', 'payment_status', 'payment_reference',
        'first_name', 'last_name', 'phone', 'email', 'address', 'city', 'district', 'note',
        'subtotal', 'shipping', 'total',
        'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at', 'confirmation_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'shipping' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
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

    public function paymentMethodLabel(): string
    {
        return $this->payment_method === 'cod' ? 'Cash on delivery' : 'Onepay';
    }

    /** Total in rupees, two decimals — the string the gateway signs and charges. */
    public function amountForGateway(): string
    {
        return number_format($this->total / 100, 2, '.', '');
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

    public function markPaymentFailed(?string $paymentReference = null): void
    {
        $this->forceFill([
            'payment_status' => 'failed',
            'payment_reference' => $paymentReference ?? $this->payment_reference,
        ])->save();
    }

    /**
     * A shopper may call off an order only while nobody has acted on it and no
     * money has changed hands. Anything past that goes through the admin, who
     * can cancel at any point before delivery.
     */
    public function canCustomerCancel(): bool
    {
        return $this->status === 'pending' && $this->payment_status !== 'paid';
    }

    /** True when there is an outstanding online payment to start or retry. */
    public function isPayable(): bool
    {
        return $this->payment_method === 'onepay'
            && in_array($this->payment_status, ['pending', 'failed'], true)
            && $this->status !== 'cancelled';
    }

    /**
     * The confirmation link. Guests have no account to sign in to, so the
     * signature on this URL is what proves the order is theirs — never build
     * this path by hand from the reference.
     */
    public function thanksUrl(): string
    {
        return URL::temporarySignedRoute('orders.thanks', now()->addDays(self::LINK_TTL_DAYS), $this);
    }

    /** Tracking that runs the lookup straight away, no phone number needed. */
    public function trackUrl(): string
    {
        return URL::temporarySignedRoute('orders.track.show', now()->addDays(self::LINK_TTL_DAYS), $this);
    }

    public function cancelUrl(): string
    {
        return URL::temporarySignedRoute('orders.cancel', now()->addDays(self::LINK_TTL_DAYS), $this);
    }

    /** Where Onepay returns the customer's browser after the hosted checkout. */
    public function paymentReturnUrl(): string
    {
        return URL::temporarySignedRoute('payment.return', now()->addDays(self::LINK_TTL_DAYS), $this);
    }

    public function payUrl(): string
    {
        return URL::temporarySignedRoute('payment.start', now()->addDays(self::LINK_TTL_DAYS), $this);
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
