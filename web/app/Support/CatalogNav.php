<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * The public sidebar: categories grouped under their parent, each with its published count,
 * and the brands that have published products. Cached; product saves call forget().
 */
class CatalogNav
{
    private const KEY = 'catalog.nav';

    public static function get(): array
    {
        return Cache::remember(self::KEY, 300, function () {
            $leaves = Category::leaves()->withCount(['products' => fn ($q) => $q->published()])->get();
            $node = fn (Category $c) => ['name' => $c->name, 'slug' => $c->slug, 'icon' => $c->icon ?: 'box', 'n' => $c->products_count];

            $groups = [];
            $loose = [];
            foreach ($leaves->groupBy('parent_id') as $parentId => $items) {
                $parent = $parentId ? Category::find($parentId) : null;
                if ($parent) {
                    $groups[] = ['name' => $parent->name, 'sort' => $parent->sort, 'items' => $items->map($node)->values()->all()];
                } else {
                    $loose = array_merge($loose, $items->map($node)->values()->all());
                }
            }
            usort($groups, fn ($a, $b) => $a['sort'] <=> $b['sort']);
            if ($loose) {
                $groups[] = ['name' => 'سایر دسته‌ها', 'sort' => 999, 'items' => $loose];
            }

            return [
                'groups' => $groups,
                'brands' => Brand::active()->whereHas('products', fn ($q) => $q->published())->withCount(['products' => fn ($q) => $q->published()])->get()
                    ->map(fn ($b) => ['name' => $b->name, 'n' => $b->products_count])->values()->all(),
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
