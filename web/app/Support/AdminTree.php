<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * The catalog tree in the admin side panel: groups → categories, each with its count
 * and how many products are still incomplete. ~400 products, audited once and cached briefly;
 * product saves and file updates call forget().
 */
class AdminTree
{
    private const KEY = 'admin.tree';

    public static function get(): array
    {
        return Cache::remember(self::KEY, 120, function () {
            $products = Product::query()->with(['category.fields', 'values', 'media', 'protocols'])->get();
            $byCat = $products->groupBy('category_id')->map(fn ($ps) => [
                'n' => $ps->count(),
                'open' => $ps->filter(fn ($p) => ProductAudit::check($p)['pct'] < 100)->count(),
            ]);
            $node = fn (Category $c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon ?: 'box'] + ($byCat[$c->id] ?? ['n' => 0, 'open' => 0]);

            $groups = [];
            $loose = [];
            foreach (Category::query()->whereNull('parent_id')->with('children')->orderBy('sort')->get() as $top) {
                if ($top->children->isNotEmpty()) {
                    $groups[] = ['name' => $top->name, 'items' => $top->children->map($node)->all()];
                } else {
                    $loose[] = $node($top);
                }
            }
            if ($loose) {
                $groups[] = ['name' => 'سایر دسته‌ها', 'items' => $loose];
            }

            return [
                'groups' => $groups,
                'all' => $products->count(),
                'draft' => $products->where('status', 'draft')->count(),
                'open' => $byCat->sum('open'),
                'trashed' => Product::onlyTrashed()->count(),
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
