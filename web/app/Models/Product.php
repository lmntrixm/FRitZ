<?php

namespace App\Models;

use App\Support\Fa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use MassPrunable, SoftDeletes;

    protected $guarded = ['id'];

    /** Never serialised: the public catalog shows no price (client rule). */
    protected $hidden = ['internal_price', 'search_text', 'code_compact', 'created_by', 'updated_by'];

    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (Product $p) {
            if (! $p->slug) {
                // the slug stays fixed after creation so public links keep working
                $base = Str::slug($p->model_code) ?: Str::lower(Str::random(8));
                $slug = $base;
                for ($i = 2; static::withTrashed()->where('slug', $slug)->whereKeyNot($p->id)->exists(); $i++) {
                    $slug = $base.'-'.$i;
                }
                $p->slug = $slug;
            }
            $p->code_compact = Fa::compact($p->model_code);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function protocols(): BelongsToMany
    {
        return $this->belongsToMany(Protocol::class)->orderBy('sort');
    }

    /** Only the protocols the public catalog may show (KNX, Zigbee, Modbus). */
    public function publicProtocols()
    {
        return $this->protocols->where('is_active', true)->values();
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductValue::class);
    }

    public function customSpecs(): HasMany
    {
        return $this->hasMany(ProductCustomSpec::class)->orderBy('sort');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'product_media')
            ->withPivot(['role', 'is_primary', 'sort'])->orderByPivot('is_primary', 'desc')->orderByPivot('sort');
    }

    public function images()
    {
        return $this->media->where('pivot.role', 'image')->values();
    }

    public function datasheet(): ?Media
    {
        return $this->media->firstWhere('pivot.role', 'datasheet');
    }

    public function compatible(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_id')
            ->wherePivot('type', 'compatible')->orderByPivot('sort');
    }

    public function related(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_id')
            ->wherePivot('type', 'related')->orderByPivot('sort');
    }

    /** Deleted products can be restored for 30 days, then they are removed for good. */
    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<', now()->subDays(30));
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->whereHas('brand', fn ($b) => $b->where('is_active', true))
            ->whereHas('category', fn ($c) => $c->where('is_active', true));
    }

    public function scopeCatalog(Builder $q): Builder
    {
        return $q->published()->with(['brand', 'category', 'protocols', 'media']);
    }

    /** Placeholder drawing kind for products without a real photo yet. */
    public function art(): string
    {
        return $this->category?->art ?: 'panel7';
    }

    /** Rebuild the normalised search text; call after brand/category/name change. */
    public function refreshSearchText(): void
    {
        $this->loadMissing(['brand', 'category']);
        $this->search_text = Fa::normalize(implode(' ', [
            $this->name, $this->model_code, $this->brand?->name, $this->category?->name,
        ]));
        $this->saveQuietly();
    }

    /** Compact data the browser keeps in the inquiry list and compare bar. */
    public function card(): array
    {
        $img = $this->images()->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->model_code,
            'brand' => $this->brand?->name,
            'art' => $this->art(),
            'img' => $img?->url(),
            'url' => route('product', $this),
        ];
    }
}
