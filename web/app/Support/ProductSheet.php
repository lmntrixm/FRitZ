<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\Product;
use App\Models\ProductUpdate;
use App\Models\Protocol;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The product sheet: one row per product, keyed by «کد مدل».
 * - export(): the whole catalog in this layout (also the template people edit and upload back)
 * - analyse(): read rows, resolve names to records, report problems, diff against the catalog
 * - apply() / rollback(): write the diff inside one transaction, with a JSON backup of every touched product
 *
 * A column that is absent from the file is left alone; a column that is present but empty clears the field.
 * Photos, datasheets and relations are not in the sheet and are never touched by an update.
 */
class ProductSheet
{
    public const COLS = [
        'code' => 'کد مدل', 'name' => 'نام', 'brand' => 'برند', 'category' => 'دسته', 'protocols' => 'پروتکل',
        'country' => 'کشور سازنده', 'status' => 'وضعیت', 'featured' => 'منتخب', 'description' => 'توضیحات',
        'pitch' => 'دلیل پیشنهاد', 'specs' => 'مشخصات دلخواه', 'price' => 'قیمت داخلی',
    ];

    public const SPEC = 'مشخصه: ';

    private const STATUS = ['published' => 'منتشرشده', 'draft' => 'پیش‌نویس'];

    /** @var Collection<int, Category> */
    private Collection $cats;

    /** @var Collection<int, Brand> */
    private Collection $brands;

    /** @var Collection<int, Protocol> */
    private Collection $protos;

    public function __construct()
    {
        $this->cats = Category::leaves()->with('fields.options')->get();
        $this->brands = Brand::query()->orderBy('sort')->get();
        $this->protos = Protocol::query()->orderBy('sort')->get();
    }

    /* ------------------------------------------------------------------ export */

    /** Header: fixed columns, then one «مشخصه: …» column per distinct field label (unit in brackets). */
    public function header(): array
    {
        $specs = $this->cats->flatMap(fn ($c) => $c->fields)->map(fn ($f) => $this->specHeader($f))->unique()->values()->all();

        return array_merge(array_values(self::COLS), $specs);
    }

    private function specHeader(CategoryAttribute $f): string
    {
        return self::SPEC.$f->label.($f->unit ? ' ('.$f->unit.')' : '');
    }

    public function export(string $path, bool $withProducts = true): int
    {
        $header = $this->header();
        $rows = [];
        if ($withProducts) {
            $products = Product::query()->with(['brand', 'category', 'protocols', 'values.option', 'customSpecs'])->orderBy('category_id')->orderBy('sort')->get();
            foreach ($products as $p) {
                $r = $this->render($p);
                $rows[] = array_map(fn ($h) => $r[$h] ?? '', $header);
            }
        }
        Xlsx::write($path, $header, $rows);

        return count($rows);
    }

    /** The product as sheet cells: [header => canonical string]. Same strings the diff compares. */
    public function render(Product $p): array
    {
        $p->loadMissing(['brand', 'category', 'protocols', 'values.option', 'customSpecs']);
        $out = [
            self::COLS['code'] => $p->model_code,
            self::COLS['name'] => $p->name,
            self::COLS['brand'] => $p->brand?->name ?? '',
            self::COLS['category'] => $p->category?->name ?? '',
            self::COLS['protocols'] => $p->protocols->where('is_active', true)->sortBy('sort')->pluck('name')->join('، '),
            self::COLS['country'] => (string) $p->country_of_origin,
            self::COLS['status'] => self::STATUS[$p->status] ?? $p->status,
            self::COLS['featured'] => $p->is_featured ? 'بله' : 'خیر',
            self::COLS['description'] => (string) $p->description,
            self::COLS['pitch'] => (string) $p->pitch,
            self::COLS['specs'] => $p->customSpecs->map(fn ($s) => $s->label.': '.$s->value)->join("\n"),
            self::COLS['price'] => $p->internal_price !== null ? (string) (int) $p->internal_price : '',
        ];
        $cat = $this->cats->firstWhere('id', $p->category_id);
        foreach ($cat?->fields ?? [] as $f) {
            $vals = $p->values->where('attribute_id', $f->id);
            $out[$this->specHeader($f)] = match ($f->type) {
                'select', 'multi' => $vals->map(fn ($v) => $v->option?->label)->filter()->join('، '),
                'number' => $vals->first()?->num !== null ? CatalogFilter::numKey($vals->first()->num) : '',
                'bool' => $vals->first()?->bool === null ? '' : ($vals->first()->bool ? 'بله' : 'خیر'),
                default => (string) $vals->first()?->text,
            };
        }

        return $out;
    }

    /* ------------------------------------------------------------------ analyse */

    /**
     * @param  array<int, array<int, string>>  $rows  as read by Xlsx::read (row 0 = header)
     * @return array{present: array, messages: array, items: array, absent: array, counts: array}
     */
    public function analyse(array $rows): array
    {
        $msgs = [];
        $msg = function (string $level, string $text, ?int $row = null) use (&$msgs) {
            $k = $level.'|'.$text;
            $msgs[$k] ??= ['level' => $level, 'text' => $text, 'rows' => []];
            if ($row !== null) {
                $msgs[$k]['rows'][] = $row;
            }
        };

        $headerRow = array_key_first($rows);
        if ($headerRow === null) {
            return $this->result([], [['level' => 'error', 'text' => 'فایل خالی است.', 'rows' => []]], [], []);
        }
        $header = array_map(fn ($h) => trim((string) $h), $rows[$headerRow]);
        $byHeader = array_flip(array_map([Fa::class, 'normalize'], self::COLS));
        $map = []; // column index => fixed key or ['spec', label]
        foreach ($header as $i => $h) {
            if ($h === '') {
                continue;
            }
            if (isset($byHeader[Fa::normalize($h)])) {
                $map[$i] = $byHeader[Fa::normalize($h)];
            } elseif (str_starts_with($h, trim(self::SPEC))) {
                $label = trim(preg_replace('/\s*\([^)]*\)\s*$/u', '', trim(mb_substr($h, mb_strlen(trim(self::SPEC))))));
                $map[$i] = ['spec', $label];
            } else {
                $msg('warning', 'ستون «'.$h.'» شناخته نشد و نادیده گرفته شد.');
            }
        }
        if (! in_array('code', $map, true)) {
            return $this->result([], [['level' => 'error', 'text' => 'ستون «کد مدل» در ردیف اول فایل پیدا نشد. از «خروجی اکسل» یا قالب نمونه استفاده کنید.', 'rows' => []]], [], []);
        }
        $present = array_values(array_unique(array_map(fn ($m) => is_array($m) ? self::SPEC.$m[1] : $m, $map)));

        // collect rows by code; duplicates skip both
        $byCode = [];
        foreach ($rows as $r => $cells) {
            if ($r === $headerRow || ! array_filter($cells, fn ($c) => trim((string) $c) !== '')) {
                continue;
            }
            $line = ['row' => $r + 1, 'fixed' => [], 'spec' => []];
            foreach ($map as $i => $m) {
                $v = trim(str_replace("\r\n", "\n", (string) ($cells[$i] ?? '')));
                is_array($m) ? $line['spec'][$m[1]] = $v : $line['fixed'][$m] = $v;
            }
            $code = $line['fixed']['code'] ?? '';
            if ($code === '') {
                $msg('error', 'کد مدل خالی است؛ ردیف رد شد.', $line['row']);

                continue;
            }
            $byCode[Str::upper($code)][] = $line;
        }

        $existing = Product::withTrashed()->with(['brand', 'category', 'protocols', 'values.option', 'customSpecs'])->get()
            ->keyBy(fn ($p) => Str::upper($p->model_code));
        $items = [];
        foreach ($byCode as $key => $lines) {
            if (count($lines) > 1) {
                foreach ($lines as $l) {
                    $msg('error', 'کد تکراری '.$lines[0]['fixed']['code'].'؛ همه ردیف‌هایش رد شد.', $l['row']);
                }

                continue;
            }
            $item = $this->resolve($lines[0], $existing->get($key), $msg);
            if ($item) {
                $items[] = $item;
            }
        }

        $inFile = array_flip(array_keys($byCode));
        $absent = $existing->filter(fn ($p, $k) => ! $p->trashed() && ! isset($inFile[$k]))
            ->map(fn ($p) => ['id' => $p->id, 'code' => $p->model_code, 'name' => $p->name])->values()->all();

        return $this->result($present, array_values($msgs), $items, $absent);
    }

    private function result(array $present, array $messages, array $items, array $absent): array
    {
        $c = collect($items)->countBy('kind');

        return [
            'present' => $present, 'messages' => $messages, 'items' => $items, 'absent' => $absent,
            'counts' => ['new' => $c['new'] ?? 0, 'mod' => $c['mod'] ?? 0, 'same' => $c['same'] ?? 0, 'absent' => count($absent),
                'fields' => collect($items)->sum(fn ($i) => count($i['changes'])),
                'skipped' => collect($messages)->where('level', 'error')->sum(fn ($m) => max(1, count($m['rows'])))],
        ];
    }

    /** One row → resolved values, the canonical «after» strings and the field changes. */
    private function resolve(array $line, ?Product $p, callable $msg): ?array
    {
        $f = $line['fixed'];
        $row = $line['row'];
        $has = fn ($k) => array_key_exists($k, $f);
        $data = ['code' => $f['code']];

        $find = fn (Collection $list, string $v) => $list->first(fn ($x) => Fa::normalize($x->name) === Fa::normalize($v) || (isset($x->slug) && Fa::normalize($x->slug) === Fa::normalize($v)));

        if (! $p) {
            foreach (['name' => 'نام', 'brand' => 'برند', 'category' => 'دسته'] as $k => $l) {
                if (! $has($k) || $f[$k] === '') {
                    $msg('error', 'محصول جدید بدون «'.$l.'»؛ ردیف رد شد.', $row);

                    return null;
                }
            }
        }
        if ($has('name') && $f['name'] !== '') {
            $data['name'] = $f['name'];
        }
        if ($has('brand') && $f['brand'] !== '') {
            $b = $find($this->brands, $f['brand']);
            if (! $b) {
                $msg('error', 'برند «'.$f['brand'].'» در پنل تعریف نشده؛ ردیف رد شد.', $row);

                return null;
            }
            $data['brand_id'] = $b->id;
        }
        if ($has('category') && $f['category'] !== '') {
            $c = $find($this->cats, $f['category']);
            if (! $c) {
                $msg('error', 'دسته «'.$f['category'].'» در پنل تعریف نشده؛ ردیف رد شد.', $row);

                return null;
            }
            $data['category_id'] = $c->id;
        }
        if ($has('protocols')) {
            $ids = [];
            foreach ($this->split($f['protocols']) as $name) {
                $x = $find($this->protos, $name);
                if (! $x) {
                    $msg('warning', 'پروتکل ناشناخته «'.$name.'» نادیده گرفته شد.', $row);
                } elseif (! $x->is_active) {
                    $msg('warning', 'پروتکل غیرفعال '.$x->name.' نادیده گرفته شد.', $row);
                } else {
                    $ids[] = $x->id;
                }
            }
            $data['protocols'] = array_values(array_unique($ids));
        }
        foreach (['country' => 'country_of_origin', 'description' => 'description', 'pitch' => 'pitch'] as $k => $col) {
            if ($has($k)) {
                $data[$col] = $f[$k] === '' ? null : $f[$k];
            }
        }
        if ($has('status') && $f['status'] !== '') {
            $st = collect(self::STATUS)->search(fn ($l, $k) => in_array(Fa::normalize($f['status']), [Fa::normalize($l), $k], true));
            $st ? $data['status'] = $st : $msg('warning', 'وضعیت «'.$f['status'].'» شناخته نشد؛ «منتشرشده» یا «پیش‌نویس» بنویسید.', $row);
        }
        if ($has('featured') && $f['featured'] !== '') {
            $b = $this->bool($f['featured']);
            $b === null ? $msg('warning', 'مقدار «منتخب» باید بله یا خیر باشد.', $row) : $data['is_featured'] = $b;
        }
        if ($has('price')) {
            $num = preg_replace('/[^\d.]/', '', Fa::normalize($f['price']));
            if ($f['price'] === '') {
                $data['internal_price'] = null;
            } elseif ($num === '' || ! is_numeric($num)) {
                $msg('warning', 'قیمت «'.$f['price'].'» عدد نیست و نادیده گرفته شد.', $row);
            } else {
                $data['internal_price'] = (int) round((float) $num);
            }
        }
        if ($has('specs')) {
            $data['specs'] = collect(preg_split('/\n|؛|;/u', $f['specs']))->map('trim')->filter()
                ->map(function ($l) {
                    [$a, $b] = array_pad(preg_split('/[:：]/u', $l, 2), 2, '');

                    return ['label' => trim($a), 'value' => trim($b)];
                })->filter(fn ($s) => $s['label'] !== '' && $s['value'] !== '')->values()->all();
        }

        // category fields
        $cat = $this->cats->firstWhere('id', $data['category_id'] ?? $p?->category_id);
        $data['values'] = [];
        foreach ($line['spec'] as $label => $raw) {
            $field = $cat?->fields->first(fn ($x) => Fa::normalize($x->label) === Fa::normalize($label));
            if (! $field) {
                if ($raw !== '') {
                    $msg('warning', 'مشخصه «'.$label.'» برای دسته «'.($cat?->name ?? '—').'» تعریف نشده و نادیده گرفته شد.', $row);
                }

                continue;
            }
            $parsed = $this->fieldValue($field, $raw);
            if ($parsed === false) {
                $msg('warning', 'مقدار «'.$raw.'» برای «'.$field->label.'» معتبر نیست و نادیده گرفته شد.', $row);

                continue;
            }
            $data['values'][$field->id] = $parsed;
        }

        // canonical after-strings, compared with the current render
        $before = $p ? $this->render($p) : [];
        $after = $this->renderData($data, $p, $cat);
        $changes = [];
        foreach ($after as $h => $a) {
            $b = $before[$h] ?? '';
            if (! $p || Fa::normalize($a) !== Fa::normalize($b)) {
                if ($p || $a !== '') {
                    $changes[] = ['f' => $h, 'b' => $p ? $b : '', 'a' => $a];
                }
            }
        }
        if ($p?->trashed()) {
            $msg('warning', 'این محصول در «حذف‌شده‌ها» است و بازگردانی می‌شود.', $row);
            $changes[] = ['f' => 'حذف‌شده', 'b' => 'بله', 'a' => 'خیر'];
        }

        return [
            'row' => $row, 'code' => $data['code'], 'name' => $data['name'] ?? $p?->name, 'product_id' => $p?->id,
            'kind' => ! $p ? 'new' : ($changes ? 'mod' : 'same'), 'restore' => (bool) $p?->trashed(),
            'changes' => $changes, 'data' => $changes ? $data : null,
        ];
    }

    /** @return array|false option ids / num / bool / text, or false when the value does not fit */
    private function fieldValue(CategoryAttribute $f, string $raw): array|false
    {
        if ($raw === '') {
            return ['empty' => true];
        }

        return match ($f->type) {
            'select', 'multi' => (function () use ($f, $raw) {
                $ids = [];
                foreach ($f->type === 'multi' ? $this->split($raw) : [$raw] as $part) {
                    $o = $f->options->first(fn ($o) => in_array(Fa::normalize($part), [Fa::normalize($o->label), Fa::normalize($o->value)], true));
                    if (! $o) {
                        return false;
                    }
                    $ids[] = $o->id;
                }

                return ['options' => $ids];
            })(),
            'number' => is_numeric($n = preg_replace('/[^\d.\-]/', '', str_replace('٫', '.', Fa::normalize($raw)))) ? ['num' => (float) $n] : false,
            'bool' => ($b = $this->bool($raw)) === null ? false : ['bool' => $b],
            default => ['text' => mb_substr($raw, 0, 190)],
        };
    }

    /** The «after» cells for the columns the file brought. */
    private function renderData(array $d, ?Product $p, ?Category $cat): array
    {
        $out = [self::COLS['code'] => $d['code']];
        $set = fn ($k) => array_key_exists($k, $d);
        if ($set('name')) {
            $out[self::COLS['name']] = $d['name'];
        }
        if ($set('brand_id')) {
            $out[self::COLS['brand']] = $this->brands->firstWhere('id', $d['brand_id'])->name;
        }
        if ($set('category_id')) {
            $out[self::COLS['category']] = $cat?->name ?? '';
        }
        if ($set('protocols')) {
            $out[self::COLS['protocols']] = $this->protos->whereIn('id', $d['protocols'])->sortBy('sort')->pluck('name')->join('، ');
        }
        foreach (['country_of_origin' => 'country', 'description' => 'description', 'pitch' => 'pitch'] as $col => $k) {
            if ($set($col)) {
                $out[self::COLS[$k]] = (string) $d[$col];
            }
        }
        if ($set('status')) {
            $out[self::COLS['status']] = self::STATUS[$d['status']];
        } elseif (! $p) {
            $out[self::COLS['status']] = self::STATUS['draft'];
        }
        if ($set('is_featured')) {
            $out[self::COLS['featured']] = $d['is_featured'] ? 'بله' : 'خیر';
        }
        if ($set('internal_price')) {
            $out[self::COLS['price']] = $d['internal_price'] === null ? '' : (string) $d['internal_price'];
        }
        if ($set('specs')) {
            $out[self::COLS['specs']] = collect($d['specs'])->map(fn ($s) => $s['label'].': '.$s['value'])->join("\n");
        }
        foreach ($d['values'] as $fid => $v) {
            $f = $cat->fields->firstWhere('id', $fid);
            $out[$this->specHeader($f)] = match (true) {
                isset($v['empty']) => '',
                isset($v['options']) => $f->options->whereIn('id', $v['options'])->pluck('label')->join('، '),
                isset($v['num']) => CatalogFilter::numKey($v['num']),
                isset($v['bool']) => $v['bool'] ? 'بله' : 'خیر',
                default => $v['text'],
            };
        }

        return $out;
    }

    private function split(string $s): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[،,\/|]+/u', $s)), fn ($x) => $x !== ''));
    }

    private function bool(string $s): ?bool
    {
        $n = Fa::normalize($s);

        return match (true) {
            in_array($n, ['بله', 'آره', 'yes', 'y', '1', 'true', 'دارد'], true) => true,
            in_array($n, ['خیر', 'نه', 'no', 'n', '0', 'false', 'ندارد'], true) => false,
            default => null,
        };
    }

    /* ------------------------------------------------------------------ apply / rollback */

    /** @return array{added:int, modified:int, deleted:int, drafted:array} */
    public function apply(ProductUpdate $u, bool $deleteAbsent, bool $ignorePrice): array
    {
        $diff = $u->diff;
        $items = collect($diff['items'])->whereIn('kind', ['new', 'mod']);
        $absentIds = $deleteAbsent ? collect($diff['absent'])->pluck('id')->all() : [];
        $touchIds = $items->pluck('product_id')->filter()->merge($absentIds)->unique()->values();

        $backup = ['update' => $u->id, 'at' => now()->toIso8601String(), 'created' => [], 'products' => $touchIds->map(fn ($id) => $this->snapshot($id))->filter()->values()->all()];
        $report = ['added' => 0, 'modified' => 0, 'deleted' => 0, 'drafted' => []];

        DB::transaction(function () use ($u, $items, $absentIds, $ignorePrice, &$backup, &$report) {
            foreach ($items as $it) {
                $d = $it['data'];
                $p = $it['product_id'] ? Product::withTrashed()->find($it['product_id']) : new Product(['created_by' => auth()->id()]);
                if ($p->trashed()) {
                    $p->restore();
                }
                $fill = array_intersect_key($d, array_flip(['name', 'brand_id', 'category_id', 'country_of_origin', 'description', 'pitch', 'status', 'is_featured']));
                if (! $ignorePrice && array_key_exists('internal_price', $d)) {
                    $fill['internal_price'] = $d['internal_price'];
                }
                $p->fill($fill + ['model_code' => $d['code'], 'updated_by' => auth()->id()]);
                $p->status ??= 'draft';
                $categoryChanged = $p->exists && $p->isDirty('category_id');
                if ($p->status === 'published' && ! $p->published_at) {
                    $p->published_at = now();
                }
                $p->save();
                if (! $it['product_id']) {
                    $backup['created'][] = $p->id;
                }

                if (array_key_exists('protocols', $d)) {
                    $p->protocols()->sync(array_merge($d['protocols'], $p->protocols()->where('is_active', false)->pluck('protocols.id')->all()));
                }
                if ($categoryChanged) {
                    $p->values()->delete(); // fields belong to the old category
                }
                foreach ($d['values'] as $fid => $v) {
                    $p->values()->where('attribute_id', $fid)->delete();
                    $rows = match (true) {
                        isset($v['empty']) => [],
                        isset($v['options']) => array_map(fn ($o) => ['option_id' => $o], $v['options']),
                        isset($v['num']) => [['num' => $v['num']]],
                        isset($v['bool']) => [['bool' => $v['bool']]],
                        default => [['text' => $v['text']]],
                    };
                    foreach ($rows as $r) {
                        $p->values()->create(['attribute_id' => $fid] + $r);
                    }
                }
                if (array_key_exists('specs', $d)) {
                    $p->customSpecs()->delete();
                    foreach ($d['specs'] as $i => $s) {
                        $p->customSpecs()->create($s + ['sort' => $i]);
                    }
                }

                // the same publish rule as the form: a missing required field keeps it a draft
                if ($p->status === 'published' && ($blockers = ProductAudit::check($p->fresh())['blockers'])) {
                    $p->update(['status' => 'draft']);
                    $report['drafted'][] = $p->model_code.' ('.implode('، ', $blockers).')';
                }
                $p->refreshSearchText();
                $it['product_id'] ? $report['modified']++ : $report['added']++;
            }
            foreach (Product::whereKey($absentIds)->get() as $p) {
                $p->delete();
                $report['deleted']++;
            }

            // inside the transaction: no backup on disk, no change in the catalog
            $name = 'BK-'.now()->format('Ymd-His').'-'.$u->id.'.json';
            if (! Storage::disk('local')->put('backups/'.$name, json_encode($backup, JSON_UNESCAPED_UNICODE))) {
                throw new \RuntimeException('نسخه پشتیبان روی سرور ذخیره نشد.');
            }
            $u->backup_path = 'backups/'.$name;
        });

        return $report;
    }

    private function snapshot(int $id): ?array
    {
        $p = Product::withTrashed()->with(['protocols', 'values', 'customSpecs'])->find($id);

        return $p ? [
            'attributes' => collect($p->getAttributes())->except(['id'])->all(),
            'id' => $p->id,
            'protocols' => $p->protocols->pluck('id')->all(),
            'values' => $p->values->map(fn ($v) => $v->only(['attribute_id', 'option_id', 'num', 'bool', 'text']))->all(),
            'specs' => $p->customSpecs->map(fn ($s) => $s->only(['label', 'value', 'sort']))->all(),
        ] : null;
    }

    public function rollback(ProductUpdate $u): void
    {
        $backup = json_decode((string) Storage::disk('local')->get($u->backup_path), true);
        DB::transaction(function () use ($backup) {
            foreach ($backup['created'] as $id) {
                Product::withTrashed()->find($id)?->forceDelete();
            }
            foreach ($backup['products'] as $snap) {
                $p = Product::withTrashed()->find($snap['id']);
                if (! $p) {
                    continue;
                }
                $p->forceFill($snap['attributes'])->saveQuietly();
                $p->protocols()->sync($snap['protocols']);
                $p->values()->delete();
                foreach ($snap['values'] as $v) {
                    $p->values()->create($v);
                }
                $p->customSpecs()->delete();
                foreach ($snap['specs'] as $s) {
                    $p->customSpecs()->create($s);
                }
            }
        });
    }
}
