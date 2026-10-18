<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Fa;
use App\Support\Search;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** Full-screen search (M-Search); with ?q= it lists every match. */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $results = $q !== '' ? (Search::query($q)?->limit(60)->get() ?? collect()) : null;

        return view('catalog.search', [
            'q' => $q,
            'results' => $results,
            'popular' => Setting::get('popular_searches', []),
            'maybe' => $results !== null && $results->isEmpty() ? $this->maybe($q) : [],
        ]);
    }

    /** Live suggestions while typing: up to 5 products plus brand/category shortcuts. */
    public function suggest(Request $request)
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 64);
        $query = Search::query($q);
        if (! $query) {
            return response()->json(['products' => [], 'links' => [], 'total' => 0]);
        }
        $total = (clone $query)->count();
        $products = $query->limit(5)->get()->map(fn (Product $p) => [
            'name' => $p->name, 'brand' => $p->brand->name, 'art' => $p->art(),
            'img' => $p->images()->first()?->url(), 'url' => route('product', $p),
            'code' => Search::highlight($p->model_code, $q),
        ]);

        $links = [];
        $norm = Fa::normalize($q);
        $brand = Brand::active()->get()->first(fn ($b) => str_starts_with(Fa::normalize($b->name), $norm)
            || ($products->isNotEmpty() && $products->every(fn ($p) => $p['brand'] === $b->name)));
        if ($brand) {
            $top = Product::published()->where('brand_id', $brand->id)
                ->selectRaw('category_id, count(*) as n')->groupBy('category_id')->orderByDesc('n')->first();
            if ($top) {
                $cat = Category::find($top->category_id);
                $links[] = ['icon' => $cat->icon ?: 'layers', 'pre' => $cat->name.'‌های', 'brand' => $brand->name,
                    'n' => Fa::digits($top->n).' محصول', 'url' => route('category', $cat).'?b[]='.$brand->slug];
            }
        }

        return response()->json([
            'products' => $products, 'links' => $links, 'total' => $total,
            'totalLabel' => Fa::digits($total), 'all' => route('search', ['q' => $q]),
        ]);
    }

    /** «شاید منظورتان این بود»: categories that share a word with the query. */
    private function maybe(string $q): array
    {
        $words = array_filter(explode(' ', Fa::normalize($q)), fn ($w) => mb_strlen($w) >= 2 && ! is_numeric($w));

        return Category::leaves()->get()
            ->filter(fn ($c) => collect($words)->contains(fn ($w) => str_contains(Fa::normalize($c->name), $w)))
            ->take(4)->map(fn ($c) => ['label' => $c->name, 'url' => route('category', $c)])->values()->all();
    }
}
