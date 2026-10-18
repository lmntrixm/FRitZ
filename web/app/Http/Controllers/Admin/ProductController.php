<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\Product;
use App\Models\Protocol;
use App\Support\Activity;
use App\Support\Fa;
use App\Support\MediaStore;
use App\Support\ProductAudit;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Products (A-Products, A-ProductEdit, A-FlowEdit). */
class ProductController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request)
    {
        $f = [
            'q' => trim((string) $request->query('q', '')),
            'brand' => (int) $request->query('brand'),
            'category' => (int) $request->query('category'),
            'protocol' => (int) $request->query('protocol'),
            'status' => in_array($request->query('status'), ['published', 'draft', 'trashed'], true) ? $request->query('status') : '',
            'incomplete' => $request->boolean('incomplete'),
            'sort' => in_array($request->query('sort'), ['least', 'name'], true) ? $request->query('sort') : 'updated',
            'view' => in_array($request->query('view'), ['table', 'board'], true) ? $request->query('view') : 'list',
            'group' => in_array($request->query('group'), ['category', 'brand'], true) ? $request->query('group') : 'status',
        ];
        if ($f['status'] === 'trashed' && $f['view'] === 'board') {
            $f['view'] = 'list';
        }

        $q = Product::query()->with(['brand', 'category.fields', 'protocols', 'media', 'values', 'updater'])
            ->when($f['status'] === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when(in_array($f['status'], ['published', 'draft'], true), fn ($q) => $q->where('status', $f['status']))
            ->when($f['brand'], fn ($q) => $q->where('brand_id', $f['brand']))
            ->when($f['category'], fn ($q) => $q->where('category_id', $f['category']))
            ->when($f['protocol'], fn ($q) => $q->whereHas('protocols', fn ($w) => $w->whereKey($f['protocol'])))
            ->when($f['q'] !== '', function ($q) use ($f) {
                $norm = Fa::normalize($f['q']);
                $q->where(fn ($w) => $w->where('search_text', 'like', '%'.addcslashes($norm, '%_\\').'%')
                    ->orWhere('code_compact', 'like', '%'.addcslashes(Fa::compact($f['q']), '%_\\').'%'));
            });

        // ~400 products: audit in memory so «فقط ناقص‌ها» and «کمترین تکمیل» work across pages
        $rows = $q->get()->map(fn (Product $p) => ['p' => $p, 'a' => ProductAudit::check($p)]);
        if ($f['incomplete']) {
            $rows = $rows->filter(fn ($r) => $r['a']['pct'] < 100);
        }
        $rows = match ($f['sort']) {
            'least' => $rows->sortBy(fn ($r) => $r['a']['pct']),
            'name' => $rows->sortBy(fn ($r) => $r['p']->name),
            default => $rows->sortByDesc(fn ($r) => $r['p']->updated_at),
        };
        $page = max(1, (int) $request->query('page', 1));
        $paged = new LengthAwarePaginator($rows->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values(),
            $rows->count(), self::PER_PAGE, $page, ['path' => $request->url(), 'query' => $request->query()]);

        // list and board show every match (~400 at most), grouped; the table pages
        $groups = $f['view'] === 'table' ? collect() : $this->group($rows->values(), $f);

        return view('admin.products.index', [
            'rows' => $paged, 'f' => $f, 'groups' => $groups, 'total' => $rows->count(),
            'current' => $f['category'] ? Category::find($f['category']) : null,
            'counts' => ['all' => Product::count(), 'published' => Product::where('status', 'published')->count(),
                'draft' => Product::where('status', 'draft')->count(), 'trashed' => Product::onlyTrashed()->count()],
            'brands' => Brand::query()->orderBy('sort')->get(),
            'categories' => Category::leaves()->get(),
            'protocols' => Protocol::query()->orderBy('sort')->get(),
        ]);
    }

    /** @return Collection<int, array{key: string, label: string, chip: string|null, icon: string|null, rows: Collection}> */
    private function group($rows, array $f)
    {
        if ($f['status'] === 'trashed') {
            return collect([['key' => 'trashed', 'label' => 'حذف‌شده', 'chip' => 'er', 'icon' => null, 'rows' => $rows]]);
        }
        if ($f['group'] === 'status') {
            return collect([['published', 'منتشرشده', 'pub'], ['draft', 'پیش‌نویس', 'draft']])
                ->map(fn ($g) => ['key' => $g[0], 'label' => $g[1], 'chip' => $g[2], 'icon' => null, 'rows' => $rows->filter(fn ($r) => $r['p']->status === $g[0])->values()])
                ->filter(fn ($g) => $f['status'] === '' || $f['status'] === $g['key'])->values();
        }
        $key = $f['group'] === 'category' ? 'category' : 'brand';

        return $rows->groupBy(fn ($r) => $r['p']->{$key.'_id'} ?? 0)
            ->map(fn ($rs) => ['key' => $key.'-'.($rs[0]['p']->{$key.'_id'} ?? 0), 'label' => $rs[0]['p']->{$key}?->name ?? 'بدون '.($key === 'category' ? 'دسته' : 'برند'),
                'chip' => null, 'icon' => $key === 'category' ? ($rs[0]['p']->category?->icon ?: 'folder') : 'tag', 'rows' => $rs->values(),
                'sort' => $key === 'category' ? ($rs[0]['p']->category?->sort ?? 999) : ($rs[0]['p']->brand?->sort ?? 999)])
            ->sortBy('sort')->values();
    }

    /** Side peek: the row's essentials without leaving the list. */
    public function peek(Product $product)
    {
        $product->load(['brand', 'category.fields', 'protocols', 'media', 'values.option', 'updater', 'customSpecs']);

        return view('admin.products.peek', ['p' => $product, 'a' => ProductAudit::check($product)]);
    }

    /** Inline status change from the list, board or peek. Publishing follows the same rule as the form. */
    public function status(Request $request, Product $product)
    {
        $to = $request->validate(['status' => ['required', 'in:published,draft']])['status'];
        if ($to === 'published' && ($blockers = ProductAudit::check($product)['blockers'])) {
            return response()->json(['ok' => false, 'msg' => 'منتشر نشد. اول این را کامل کنید: '.implode('، ', $blockers)], 422);
        }
        if ($product->status !== $to) {
            $product->update(['status' => $to, 'published_at' => $product->published_at ?? ($to === 'published' ? now() : null), 'updated_by' => $request->user()->id]);
            Activity::log('product.'.($to === 'published' ? 'publish' : 'draft'), $product, ['name' => $product->name]);
        }

        return response()->json(['ok' => true, 'status' => $to,
            'msg' => '«'.$product->name.'» '.($to === 'published' ? 'منتشر شد و در کاتالوگ عمومی دیده می‌شود.' : 'به پیش‌نویس برگشت و از کاتالوگ عمومی برداشته شد.')]);
    }

    public function create(Request $request)
    {
        $p = new Product(['status' => 'draft', 'category_id' => (int) $request->query('category') ?: null,
            'model_code' => (string) $request->query('code', '')]);

        return $this->form($p);
    }

    public function edit(Product $product)
    {
        return $this->form($product->load(['protocols', 'values', 'customSpecs', 'media', 'compatible', 'related', 'creator', 'updater']));
    }

    private function form(Product $p)
    {
        $categories = Category::leaves()->with(['fields.options'])->get();

        return view('admin.products.form', [
            'p' => $p,
            'audit' => $p->exists ? ProductAudit::check($p) : null,
            'brands' => Brand::query()->orderBy('sort')->get(),
            'categories' => $categories,
            'protocols' => Protocol::active()->get(),
            'inactiveProtocols' => $p->exists ? $p->protocols->where('is_active', false)->values() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $p = new Product(['created_by' => $request->user()->id]);

        return $this->save($request, $p);
    }

    public function update(Request $request, Product $product)
    {
        return $this->save($request, $product);
    }

    /** One save path for create and edit. intent: draft | publish | save (keep a published product published). */
    private function save(Request $request, Product $p)
    {
        $intent = in_array($request->input('intent'), ['draft', 'publish', 'save'], true) ? $request->input('intent') : 'draft';
        $strict = $intent === 'publish' || ($intent === 'save' && $p->status === 'published');
        $category = Category::query()->whereKey($request->input('category_id'))->with('fields.options')->first();

        $rules = [
            'name' => ['required', 'string', 'max:190'],
            'model_code' => ['required', 'string', 'max:64', Rule::unique('products', 'model_code')->ignore($p->id)],
            'brand_id' => ['required', Rule::exists('brands', 'id')],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'country_of_origin' => ['nullable', 'string', 'max:60'],
            'protocols' => ['array'], 'protocols.*' => [Rule::exists('protocols', 'id')->where('is_active', true)],
            'description' => ['nullable', 'string', 'max:4000'],
            'pitch' => ['nullable', 'string', 'max:190'],
            'is_featured' => ['boolean'],
            'specs' => ['array', 'max:40'], 'specs.*.label' => ['nullable', 'string', 'max:120'], 'specs.*.value' => ['nullable', 'string', 'max:255'],
            'images' => ['array', 'max:12'], 'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'datasheet' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'media_order' => ['array'], 'media_order.*' => ['integer'],
            'compatible' => ['array', 'max:30'], 'compatible.*' => ['integer'],
            'related' => ['array', 'max:30'], 'related.*' => ['integer'],
        ];
        $names = ['name' => 'نام فارسی', 'model_code' => 'کد مدل', 'brand_id' => 'برند', 'category_id' => 'دسته',
            'country_of_origin' => 'کشور سازنده', 'description' => 'توضیحات', 'pitch' => 'دلیل پیشنهاد', 'images.*' => 'عکس',
            'datasheet' => 'دیتاشیت', 'internal_price' => 'قیمت داخلی'];
        if ($request->user()->isAdmin()) {
            $rules['internal_price'] = ['nullable', 'numeric', 'min:0', 'max:999999999999999'];
        }
        foreach ($category?->fields ?? [] as $field) {
            $key = 'f.'.$field->id;
            $names[$key] = $field->label;
            $names[$key.'.*'] = $field->label;
            $req = $strict && $field->required_to_publish ? 'required' : 'nullable';
            match ($field->type) {
                'select' => $rules[$key] = [$req, Rule::in($field->options->pluck('id')->map(fn ($v) => (string) $v)->all())],
                'multi' => [$rules[$key] = [$req, 'array'], $rules[$key.'.*'] = [Rule::in($field->options->pluck('id')->map(fn ($v) => (string) $v)->all())]],
                'number' => $rules[$key] = array_values(array_filter([$req, 'numeric',
                    $field->num_min !== null ? 'min:'.$field->num_min : null, $field->num_max !== null ? 'max:'.$field->num_max : null])),
                'bool' => $rules[$key] = ['nullable', 'in:0,1'],
                default => $rules[$key] = [$req, 'string', 'max:190'],
            };
        }

        $data = $request->validate($rules, [
            'model_code.unique' => Product::onlyTrashed()->where('model_code', $request->input('model_code'))->exists()
                ? 'این کد مدل برای یک محصول حذف‌شده ثبت است؛ آن را از «حذف‌شده‌ها» بازگردانی کنید.'
                : 'این کد مدل قبلاً برای محصول دیگری ثبت شده است.',
            'f.*.required' => 'برای انتشار لازم است.',
        ], $names);

        $before = $p->exists ? $this->snapshot($p) : null;
        $wasPublished = $p->status === 'published';

        DB::transaction(function () use ($request, $p, $data, $intent, $category) {
            $p->fill([
                'name' => trim($data['name']), 'model_code' => trim($data['model_code']),
                'brand_id' => $data['brand_id'], 'category_id' => $data['category_id'],
                'country_of_origin' => $data['country_of_origin'] ?? null, 'description' => $data['description'] ?? null,
                'pitch' => $data['pitch'] ?? null, 'is_featured' => $request->boolean('is_featured'),
                'updated_by' => $request->user()->id,
            ]);
            if ($request->user()->isAdmin()) {
                $p->internal_price = $data['internal_price'] ?? null;
            }
            $p->status = match ($intent) {
                'publish' => 'published',
                'draft' => 'draft',
                default => $p->status ?: 'draft',
            };
            if ($p->status === 'published' && ! $p->published_at) {
                $p->published_at = now();
            }
            $p->save();

            $p->protocols()->sync(array_merge($data['protocols'] ?? [],
                // inactive protocols (IP, Wi-Fi) are not editable here, so keep what the product already had
                $p->protocols()->where('is_active', false)->pluck('protocols.id')->all()));
            $this->saveValues($p, $category, $request->input('f', []));

            $p->customSpecs()->delete();
            foreach (array_values(array_filter($data['specs'] ?? [], fn ($r) => filled($r['label'] ?? null) && filled($r['value'] ?? null))) as $i => $r) {
                $p->customSpecs()->create(['label' => trim($r['label']), 'value' => trim($r['value']), 'sort' => $i]);
            }

            $this->saveMedia($request, $p);

            $sync = fn (string $type, array $ids) => collect($ids)->map('intval')->reject(fn ($id) => $id === $p->id)->unique()->values()
                ->mapWithKeys(fn ($id, $i) => [$id => ['type' => $type, 'sort' => $i]])->all();
            DB::table('product_relations')->where('product_id', $p->id)->delete();
            $p->compatible()->attach($sync('compatible', Product::whereKey($data['compatible'] ?? [])->pluck('id')->all()));
            $p->related()->attach($sync('related', Product::whereKey($data['related'] ?? [])->pluck('id')->all()));

            $p->refreshSearchText();
        });

        $p->refresh();
        $changed = $before ? array_keys(array_diff_assoc(array_map('serialize', $this->snapshot($p)), array_map('serialize', $before))) : [];
        Activity::log($before ? 'product.update' : 'product.create', $p, ['name' => $p->name, 'code' => $p->model_code, 'fields' => $changed]);
        if ($p->status === 'published' && ! $wasPublished) {
            Activity::log('product.publish', $p, ['name' => $p->name]);
        }

        $msg = match (true) {
            $p->status === 'published' && ! $wasPublished => '«'.$p->name.'» منتشر شد و در کاتالوگ عمومی دیده می‌شود.',
            $p->status === 'draft' && $wasPublished => '«'.$p->name.'» به پیش‌نویس برگشت و از کاتالوگ عمومی برداشته شد.',
            ! $before => 'محصول ساخته شد.',
            default => $changed ? 'تغییرات ذخیره شد.' : 'تغییری برای ذخیره نبود.',
        };

        return redirect()->route('admin.products.edit', $p)->with('ok', $msg);
    }

    private function saveValues(Product $p, ?Category $category, array $input): void
    {
        $p->values()->delete();
        foreach ($category?->fields ?? [] as $field) {
            $v = $input[$field->id] ?? null;
            $rows = match ($field->type) {
                'select' => filled($v) ? [['option_id' => (int) $v]] : [],
                'multi' => collect((array) $v)->filter()->map(fn ($id) => ['option_id' => (int) $id])->all(),
                'number' => filled($v) ? [['num' => (float) $v]] : [],
                'bool' => $v === '1' || $v === '0' ? [['bool' => $v === '1']] : [],
                default => filled($v) ? [['text' => trim((string) $v)]] : [],
            };
            foreach ($rows as $row) {
                $p->values()->create(['attribute_id' => $field->id] + $row);
            }
        }
    }

    private function saveMedia(Request $request, Product $p): void
    {
        $existing = $p->media()->get();
        $remove = array_map('intval', (array) $request->input('remove_media', []));
        if ($remove) {
            $p->media()->detach($remove);
        }

        // order of the kept photos as arranged in the form; the first one is the main photo
        $order = collect((array) $request->input('media_order', []))->map('intval')
            ->filter(fn ($id) => $existing->where('pivot.role', 'image')->contains('id', $id) && ! in_array($id, $remove, true))->values();
        foreach ($order as $i => $id) {
            $p->media()->updateExistingPivot($id, ['sort' => $i, 'is_primary' => $i === 0]);
        }
        $next = $order->count();
        foreach ($request->file('images', []) as $file) {
            $m = MediaStore::image($file, 'products/'.$p->id, $p->name);
            $p->media()->attach($m->id, ['role' => 'image', 'sort' => $next, 'is_primary' => $next === 0]);
            $next++;
        }

        if ($request->hasFile('datasheet')) {
            $old = $existing->where('pivot.role', 'datasheet')->pluck('id')->all();
            $p->media()->detach($old);
            $m = MediaStore::raw($request->file('datasheet'), 'products/'.$p->id, 'datasheet', $p->name);
            $p->media()->attach($m->id, ['role' => 'datasheet', 'sort' => 0, 'is_primary' => false]);
        }
    }

    /** Field-level fingerprint, to tell the activity log what actually changed. */
    private function snapshot(Product $p): array
    {
        $p->load(['protocols', 'values', 'customSpecs', 'media', 'compatible', 'related']);

        return [
            'نام' => $p->name, 'کد مدل' => $p->model_code, 'برند' => $p->brand_id, 'دسته' => $p->category_id,
            'کشور سازنده' => $p->country_of_origin, 'توضیحات' => $p->description, 'دلیل پیشنهاد' => $p->pitch,
            'وضعیت' => $p->status, 'منتخب' => $p->is_featured, 'قیمت داخلی' => (string) $p->internal_price,
            'پروتکل' => $p->protocols->pluck('id')->sort()->values()->all(),
            'مشخصات فنی' => $p->values->map(fn ($v) => [$v->attribute_id, $v->option_id, $v->num, $v->bool, $v->text])->all(),
            'مشخصات دلخواه' => $p->customSpecs->map(fn ($s) => [$s->label, $s->value])->all(),
            'رسانه' => $p->media->map(fn ($m) => [$m->id, $m->pivot->role, $m->pivot->sort])->all(),
            'ارتباط‌ها' => [$p->compatible->pluck('id')->all(), $p->related->pluck('id')->all()],
        ];
    }

    public function duplicate(Request $request, Product $product)
    {
        $copy = DB::transaction(function () use ($request, $product) {
            $code = $product->model_code.'-COPY';
            for ($i = 2; Product::withTrashed()->where('model_code', $code)->exists(); $i++) {
                $code = $product->model_code.'-COPY'.$i;
            }
            $copy = $product->replicate(['slug', 'search_text', 'code_compact', 'published_at']);
            $copy->fill(['model_code' => $code, 'status' => 'draft', 'is_featured' => false,
                'created_by' => $request->user()->id, 'updated_by' => $request->user()->id])->save();
            $copy->protocols()->sync($product->protocols->pluck('id'));
            foreach ($product->values as $v) {
                $copy->values()->create($v->only(['attribute_id', 'option_id', 'num', 'bool', 'text']));
            }
            foreach ($product->customSpecs as $s) {
                $copy->customSpecs()->create($s->only(['label', 'value', 'sort']));
            }
            foreach ($product->media as $m) {
                $copy->media()->attach($m->id, $m->pivot->only(['role', 'is_primary', 'sort']));
            }
            $copy->refreshSearchText();

            return $copy;
        });
        Activity::log('product.duplicate', $copy, ['name' => $copy->name, 'from' => $product->model_code]);

        return redirect()->route('admin.products.edit', $copy)->with('ok', 'نسخه پیش‌نویس از «'.$product->name.'» ساخته شد. کد مدل را اصلاح کنید.');
    }

    /** Bulk actions from the list: publish, draft, delete, restore. */
    public function bulk(Request $request)
    {
        $data = $request->validate(['action' => ['required', 'in:publish,draft,delete,restore'], 'ids' => ['required', 'array', 'max:200'], 'ids.*' => ['integer']]);
        $ids = $data['ids'];
        $n = 0;
        $skipped = [];

        if ($data['action'] === 'restore') {
            $n = Product::onlyTrashed()->whereKey($ids)->restore();
        } elseif ($data['action'] === 'delete') {
            $n = Product::whereKey($ids)->get()->each->delete()->count();
        } else {
            foreach (Product::whereKey($ids)->get() as $p) {
                if ($data['action'] === 'publish' && ProductAudit::check($p)['blockers']) {
                    $skipped[] = $p->name;

                    continue;
                }
                $p->update(['status' => $data['action'] === 'publish' ? 'published' : 'draft',
                    'published_at' => $p->published_at ?? ($data['action'] === 'publish' ? now() : null), 'updated_by' => $request->user()->id]);
                $n++;
            }
        }
        Activity::log('product.bulk.'.$data['action'], null, ['count' => $n, 'ids' => $ids]);

        $verb = ['publish' => 'منتشر شد', 'draft' => 'به پیش‌نویس برگشت', 'delete' => 'حذف شد', 'restore' => 'بازگردانی شد'][$data['action']];
        $msg = Fa::digits($n).' محصول '.$verb.'.';
        if ($skipped) {
            $msg .= ' '.Fa::digits(count($skipped)).' محصول به‌خاطر فیلد لازم خالی منتشر نشد: '.implode('، ', array_slice($skipped, 0, 3)).(count($skipped) > 3 ? '، …' : '');
        }
        if ($data['action'] === 'delete' && $n) {
            $msg .= ' تا ۳۰ روز از «حذف‌شده‌ها» قابل بازگردانی است.';
        }

        return back()->with('ok', $msg);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        Activity::log('product.delete', $product, ['name' => $product->name, 'code' => $product->model_code]);

        return redirect()->route('admin.products.index')->with('ok', '«'.$product->name.'» حذف شد. تا ۳۰ روز از «حذف‌شده‌ها» قابل بازگردانی است.');
    }

    /** Picker for «سازگار با» and «محصولات مرتبط». */
    public function lookup(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        return response()->json(Product::query()->with('category')
            ->where(fn ($w) => $w->where('search_text', 'like', '%'.addcslashes(Fa::normalize($q), '%_\\').'%')
                ->orWhere('code_compact', 'like', '%'.addcslashes(Fa::compact($q), '%_\\').'%'))
            ->whereKeyNot((int) $request->query('except'))->orderBy('name')->limit(8)->get()
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->model_code, 'art' => $p->art(),
                'img' => $p->images()->first()?->url(), 'url' => route('admin.products.edit', $p)]));
    }

    /** For the edit form: which fields a category brings, rendered when the category changes. */
    public static function fieldValue(Product $p, CategoryAttribute $f): mixed
    {
        $vals = $p->values->where('attribute_id', $f->id);

        return match ($f->type) {
            'select' => (string) $vals->first()?->option_id,
            'multi' => $vals->pluck('option_id')->map(fn ($v) => (string) $v)->all(),
            'number' => $vals->first()?->num !== null ? rtrim(rtrim(number_format($vals->first()->num, 3, '.', ''), '0'), '.') : '',
            'bool' => $vals->first()?->bool === null ? '' : ($vals->first()->bool ? '1' : '0'),
            default => (string) $vals->first()?->text,
        };
    }
}
