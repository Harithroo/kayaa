<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SizeOption extends Model
{
    protected $fillable = [
        'size_scale_id', 'label', 'position',
        'min_height_cm', 'max_height_cm', 'min_weight_kg', 'max_weight_kg',
    ];

    public function scale(): BelongsTo
    {
        return $this->belongsTo(SizeScale::class, 'size_scale_id');
    }

    public function heightRange(): ?string
    {
        return match (true) {
            $this->min_height_cm && $this->max_height_cm => "{$this->min_height_cm}–{$this->max_height_cm} cm",
            (bool) $this->max_height_cm => "Up to {$this->max_height_cm} cm",
            (bool) $this->min_height_cm => "{$this->min_height_cm} cm+",
            default => null,
        };
    }

    public function weightRange(): ?string
    {
        $min = $this->min_weight_kg ? rtrim(rtrim((string) $this->min_weight_kg, '0'), '.') : null;
        $max = $this->max_weight_kg ? rtrim(rtrim((string) $this->max_weight_kg, '0'), '.') : null;

        return match (true) {
            $min && $max => "{$min}–{$max} kg",
            (bool) $max => "Up to {$max} kg",
            (bool) $min => "{$min} kg+",
            default => null,
        };
    }
}
