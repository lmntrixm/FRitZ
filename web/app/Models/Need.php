<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Need extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'need_category');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort');
    }
}
