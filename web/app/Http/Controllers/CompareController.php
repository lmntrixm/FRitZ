<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    /** Up to 3 products side by side; rows are the union of their categories' compare fields. */
    public function show(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))->map(fn ($v) => (int) $v)->filter()->unique()->take(3);
        $products = Product::catalog()->with(['values.option', 'category.fields'])->whereKey($ids)->get()
            ->sortBy(fn ($p) => $ids->search($p->id))->values();

        $rows = [];
        $add = function (string $k, callable $get, bool $lat = false) use (&$rows, $products) {
            $vals = $products->map($get)->all();
            if (collect($vals)->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty()) {
                $rows[] = ['k' => $k, 'v' => $vals, 'lat' => $lat, 'diff' => count(array_unique($vals)) > 1];
            }
        };
        $add('برند', fn ($p) => $p->brand->name, true);
        $add('پروتکل', fn ($p) => $p->publicProtocols()->pluck('name')->join(' / ') ?: '—', true);
        $add('دسته', fn ($p) => $p->category->name);
        $fields = $products->flatMap(fn ($p) => $p->category->fields->where('is_compare', true))->unique('key');
        foreach ($fields as $f) {
            $add($f->label, function ($p) use ($f) {
                $field = $p->category->fields->firstWhere('key', $f->key);
                $vals = $field ? $p->values->where('attribute_id', $field->id) : collect();

                return $vals->isEmpty() ? '—' : $vals->map(fn ($v) => $field->format($v))->join('، ');
            });
        }
        $add('دیتاشیت', fn ($p) => $p->datasheet() ? 'PDF' : '—', true);

        return view('catalog.compare', ['products' => $products, 'rows' => $rows]);
    }
}
