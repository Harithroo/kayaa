<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of the stock history. Append-only: there is no update path, and the
 * admin screen is read-only.
 *
 * Who records what: OrderService (placed / cancelled) uses query-builder
 * increments, which fire no model events, and calls record() itself.
 * ProductVariant's model events cover the rest (initial stock, and edits made
 * through the product form). Each change takes exactly one of those two paths,
 * so nothing is logged twice.
 */
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    public const REASONS = [
        'initial' => 'Initial stock',
        'order_placed' => 'Order placed',
        'order_cancelled' => 'Order cancelled',
        'admin_edit' => 'Admin edit',
    ];

    protected $fillable = [
        'product_variant_id', 'product_id', 'label', 'sku', 'delta', 'stock_after',
        'reason', 'order_id', 'user_id', 'note',
    ];

    protected function casts(): array
    {
        return ['delta' => 'integer', 'stock_after' => 'integer', 'created_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /**
     * Log a change that has already happened. $stockAfter is the stock level once
     * the change is applied; the caller knows it because it holds the row lock.
     */
    public static function record(
        ProductVariant $variant,
        int $delta,
        int $stockAfter,
        string $reason,
        ?Order $order = null,
        ?string $note = null,
    ): self {
        $variant->loadMissing(['product', 'sizeOption', 'color']);

        return self::create([
            'product_variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'label' => trim(($variant->product?->name ?? 'Product').' ('.$variant->label().')'),
            'sku' => $variant->sku,
            'delta' => $delta,
            'stock_after' => $stockAfter,
            'reason' => $reason,
            'order_id' => $order?->id,
            'user_id' => auth()->id(),
            'note' => $note,
        ]);
    }
}
