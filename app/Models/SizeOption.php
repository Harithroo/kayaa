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

    /** "0–3m" → "0–3 months", "2Y" → "2 years", anything else as it is ("Newborn"). */
    public function longLabel(): string
    {
        if (preg_match('/^(\d+)–(\d+)m$/u', $this->label, $m)) {
            return "{$m[1]}–{$m[2]} months";
        }
        if (preg_match('/^(\d+)Y$/i', $this->label, $m)) {
            return $m[1].' '.($m[1] === '1' ? 'year' : 'years');
        }

        return $this->label;
    }

    /** The low end of the size in a range: "Newborn" → "NB", "6–9m" → "6m", "2Y" → "2Y". */
    public function shortFrom(): string
    {
        return $this->shortEnd(1);
    }

    /** The high end of the size in a range: "Newborn" → "NB", "6–9m" → "9m", "2Y" → "2Y". */
    public function shortTo(): string
    {
        return $this->shortEnd(2);
    }

    private function shortEnd(int $end): string
    {
        if (strcasecmp($this->label, 'Newborn') === 0) {
            return 'NB';
        }
        if (preg_match('/^(\d+)–(\d+)m$/u', $this->label, $m)) {
            return $m[$end].'m';
        }

        return $this->label;
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
