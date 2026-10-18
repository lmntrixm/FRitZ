<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        abort_unless(Product::published()->whereKey($product->id)->exists(), 404);
        $product->load(['brand', 'category.fields.options', 'protocols', 'media', 'values.option', 'customSpecs']);

        $compatible = $product->compatible()->catalog()->get();
        $related = Product::catalog()->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)->orderByDesc('is_featured')->orderBy('sort')->limit(6)->get();

        return view('catalog.product', [
            'p' => $product,
            'specs' => self::specs($product),
            'compatible' => $compatible,
            'related' => $related,
        ]);
    }

    /** Spec rows: identity first, then the category's fields, then free rows. Datasheet values only. */
    public static function specs(Product $p): array
    {
        $rows = [
            ['k' => 'برند', 'v' => $p->brand->name, 'lat' => true],
            ['k' => 'کد مدل', 'v' => $p->model_code, 'lat' => true],
            ['k' => 'دسته', 'v' => $p->category->name, 'lat' => false],
        ];
        $protos = $p->publicProtocols();
        if ($protos->isNotEmpty()) {
            $rows[] = ['k' => 'پروتکل', 'v' => $protos->pluck('name')->join(' / '), 'lat' => true];
        }
        foreach ($p->category->fields as $f) {
            $vals = $p->values->where('attribute_id', $f->id);
            if ($vals->isNotEmpty()) {
                $rows[] = ['k' => $f->label, 'v' => $vals->map(fn ($v) => $f->format($v))->join('، '), 'lat' => false];
            }
        }
        foreach ($p->customSpecs as $c) {
            $rows[] = ['k' => $c->label, 'v' => $c->value, 'lat' => false];
        }
        if ($p->country_of_origin) {
            $rows[] = ['k' => 'کشور سازنده', 'v' => $p->country_of_origin, 'lat' => false];
        }

        return $rows;
    }
}
