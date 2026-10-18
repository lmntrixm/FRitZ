<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CategoryAttribute;
use App\Models\Need;
use App\Models\Product;
use App\Models\Protocol;
use App\Support\CatalogFilter;
use App\Support\Fa;
use Illuminate\Http\Request;

/** «محصول مناسبم را پیدا کن» (M-Wizard): need → protocol → brand → suggestions with a small scoped filter. */
class FinderController extends Controller
{
    public function index()
    {
        $needs = Need::active()->with('categories')->get();
        $products = Product::catalog()->get();
        $brands = Brand::active()->get();

        return view('catalog.finder', [
            'needs' => $needs->map(fn ($n) => ['id' => $n->id, 'label' => $n->label, 'hint' => $n->hint, 'icon' => $n->icon,
                'cats' => $n->categories->pluck('id')])->values(),
            'protocols' => Protocol::active()->get()->map(fn ($p) => ['code' => $p->code, 'name' => $p->name,
                'hint' => $p->description, 'icon' => $p->is_wireless ? 'wifi' : 'cable'])->values(),
            'brands' => $brands->map(fn ($b) => ['slug' => $b->slug, 'name' => $b->name,
                // «کلید، رله و تاچ پنل»: the brand's top categories, from real products
                'hint' => implode('، ', $products->where('brand_id', $b->id)->countBy('category_id')->sortDesc()->keys()->take(3)
                    ->map(fn ($id) => $products->firstWhere('category_id', $id)->category->name)->all())])->values(),
            // availability matrix so step 3 only offers brands that have products for the choices
            'matrix' => $products->map(fn ($p) => ['c' => $p->category_id, 'b' => $p->brand->slug,
                'p' => $p->publicProtocols()->pluck('code')])->values(),
        ]);
    }

    public function results(Request $request)
    {
        $needIds = array_map('intval', (array) $request->query('n', []));
        $needs = Need::active()->with('categories')->whereKey($needIds)->get();
        if ($needs->isEmpty()) {
            return redirect()->route('finder');
        }
        $protos = array_values(array_diff((array) $request->query('p', []), ['any']));
        $brandSlugs = array_values(array_diff((array) $request->query('b', []), ['any']));
        $sel = (array) $request->query('f', []);

        $catIds = $needs->flatMap(fn ($n) => $n->categories->pluck('id'))->unique();
        $base = Product::catalog()->with('values.option')->whereIn('category_id', $catIds)
            ->when($protos, fn ($q) => $q->whereHas('protocols', fn ($w) => $w->whereIn('code', $protos)))
            ->when($brandSlugs, fn ($q) => $q->whereHas('brand', fn ($w) => $w->whereIn('slug', $brandSlugs)))
            ->orderByDesc('is_featured')->orderBy('sort')->get();

        // the small scoped filter: only fields marked in_finder, only values present in these results
        $fields = CategoryAttribute::query()->whereIn('category_id', $catIds)->where('in_finder', true)
            ->with(['options', 'category'])->orderBy('sort')->get();
        $valueOf = fn (Product $p, CategoryAttribute $f) => $p->values->where('attribute_id', $f->id)
            ->map(fn ($v) => $f->hasOptions() ? $v->option?->value : CatalogFilter::numKey($v->num))->filter()->all();
        $filtered = $base->filter(function (Product $p) use ($fields, $sel, $valueOf) {
            foreach ($fields->where('category_id', $p->category_id) as $f) {
                $want = array_map('strval', (array) ($sel[$f->key] ?? []));
                if ($want && ! array_intersect($want, $valueOf($p, $f))) {
                    return false;
                }
            }

            return true;
        });

        $cards = $fields->groupBy('category_id')->map(function ($fs) use ($base, $sel, $valueOf, $request) {
            $groups = $fs->map(function (CategoryAttribute $f) use ($base, $sel, $valueOf, $request) {
                $present = $base->where('category_id', $f->category_id)->flatMap(fn ($p) => $valueOf($p, $f))->unique();
                $opts = $f->options->filter(fn ($o) => $present->contains($o->value))->map(function ($o) use ($f, $sel, $request) {
                    $cur = array_map('strval', (array) ($sel[$f->key] ?? []));
                    $on = in_array($o->value, $cur, true);
                    $next = $request->query();
                    $next['f'][$f->key] = $on ? array_values(array_diff($cur, [$o->value])) : [$o->value];

                    return ['label' => $o->label, 'swatch' => $o->swatch, 'on' => $on,
                        'url' => route('finder.results', $next)];
                })->values();

                return ['title' => $f->label, 'opts' => $opts];
            })->filter(fn ($g) => $g['opts']->count() > 1)->values();

            return ['title' => 'تنظیم '.$fs->first()->category->name, 'groups' => $groups];
        })->filter(fn ($c) => $c['groups']->isNotEmpty())->values();

        $groups = $needs->map(function (Need $n) use ($filtered, $fields, $sel) {
            $items = $filtered->whereIn('category_id', $n->categories->pluck('id'))->take(4)->values();
            $labels = $fields->whereIn('category_id', $n->categories->pluck('id'))->flatMap(
                fn ($f) => $f->options->whereIn('value', (array) ($sel[$f->key] ?? []))->pluck('label')
            );

            return ['need' => $n, 'items' => $items,
                'sub' => collect([Fa::digits($items->count()).' محصول'])->merge($labels)->join(' · ')];
        });

        $all = $groups->flatMap(fn ($g) => $g['items']);
        $first = $needs->first()->categories->first();

        return view('catalog.finder-results', [
            'chips' => $needs->pluck('label')->merge(Protocol::whereIn('code', $protos)->pluck('name'))
                ->merge(Brand::whereIn('slug', $brandSlugs)->pluck('name')),
            'cards' => $cards,
            'groups' => $groups,
            'all' => $all,
            'listUrl' => $first ? route('category', $first).'?'.http_build_query(array_filter(['p' => $protos, 'b' => $brandSlugs])) : route('home'),
        ]);
    }
}
