<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Protocol extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'is_wireless' => 'boolean'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort');
    }
}
