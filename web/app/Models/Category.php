<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class)->orderBy('sort');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Leaf categories that hold products: the 14 the public catalog shows. */
    public function scopeLeaves(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('sort');
    }
}
