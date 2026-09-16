<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SizeScale extends Model
{
    protected $fillable = ['name', 'type'];

    public function options(): HasMany
    {
        return $this->hasMany(SizeOption::class)->orderBy('position');
    }
}
