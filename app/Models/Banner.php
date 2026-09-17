<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    public const PLACEMENTS = ['topbar' => 'Top strip', 'home_promo' => 'Home promo block'];

    public const STYLES = ['ink' => 'Dark (ink)', 'cream' => 'Cream', 'butter' => 'Butter'];

    protected $fillable = [
        'placement', 'eyebrow', 'title', 'body', 'cta_label', 'cta_url',
        'style', 'is_active', 'starts_at', 'ends_at', 'position',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    /** Active now: switched on and inside its date window (both ends optional). */
    public function scopeLive(Builder $q, string $placement): Builder
    {
        return $q->where('placement', $placement)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('position');
    }

    public function isLive(): bool
    {
        return $this->is_active
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->ends_at || $this->ends_at->isFuture());
    }
}
