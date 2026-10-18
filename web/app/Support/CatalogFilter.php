<?php

namespace App\Support;

use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Filters for one category: brand, protocol and the category's own fields
 * (category_attributes with is_filter). A category holds tens of products, so
 * everything is filtered in memory and the same facet matrix is handed to the
 * filter sheet for live result counts.
 *
 * Query string: b[]=brand-slug, p[]=protocol-code, f[key][]=value, s=sort.
 */
class CatalogFilter
{
    /** @var Collection<int, Product> */
    public Collection $all;

    /** @var array<string, array<int, string>> group => selected values */
    public array $sel = [];

    public string $sort;

    /** @var array<int, array<string, array<int, string>>> */
    private array $keyCache = [];

    /** @var Collection<int, CategoryAttribute> */
    public Collection $fields;

    public function __construct(public Category $category, Request $request)
    {
        $this->fields = $category->fields()->where('is_filter', true)->with('options')->get();

        $this->all = Product::catalog()->with('values.option')
            ->where('category_id', $category->id)
            ->get();

        $this->sel['b'] = $this->clean($request->query('b'));
        $this->sel['p'] = $this->clean($request->query('p'));
        foreach ($this->fields as $f) {
            $this->sel['f.'.$f->key] = $this->clean(data_get($request->query('f'), $f->key));
        }
        $this->sel = array_filter($this->sel);
        $this->sort = in_array($request->query('s'), ['new', 'name'], true) ? $request->query('s') : 'rec';
    }

    private function clean(mixed $v): array
    {
        return array_values(array_unique(array_filter(array_map('strval', (array) $v), fn ($x) => $x !== '')));
    }

    /** The facet keys of one product: what each filter group would match. */
    public function keys(Product $p): array
    {
        return $this->keyCache[$p->id] ??= $this->computeKeys($p);
    }

    private function computeKeys(Product $p): array
    {
        $k = ['b' => [$p->brand->slug], 'p' => $p->publicProtocols()->pluck('code')->all()];
        foreach ($this->fields as $f) {
            $k['f.'.$f->key] = $p->values->where('attribute_id', $f->id)->map(fn ($v) => match ($f->type) {
                'select', 'multi' => $v->option?->value,
                'number' => self::numKey($v->num),
                'bool' => $v->bool ? '1' : '0',
                default => $v->text,
            })->filter()->values()->all();
        }

        return $k;
    }

    public static function numKey(?float $n): string
    {
        return $n === null ? '' : rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }

    /** OR inside a group, AND across groups. One selection can be left out. */
    private function matches(Product $p, ?string $skipGroup = null, ?string $skipValue = null): bool
    {
        $keys = $this->keys($p);
        foreach ($this->sel as $g => $vals) {
            if ($g === $skipGroup) {
                $vals = $skipValue === null ? [] : array_values(array_diff($vals, [$skipValue]));
                if (! $vals) {
                    continue;
                }
            }
            if (! array_intersect($vals, $keys[$g] ?? [])) {
                return false;
            }
        }

        return true;
    }

    public function results(): Collection
    {
        $r = $this->all->filter(fn ($p) => $this->matches($p));

        $r = match ($this->sort) {
            'new' => $r->sortByDesc('published_at'),
            'name' => $r->sortBy('name'),
            default => $r->sortBy([['is_featured', 'desc'], ['sort', 'asc']]),
        };

        return $r->values();
    }

    public function activeCount(): int
    {
        return array_sum(array_map('count', $this->sel));
    }

    /** Active selections as removable chips, each with the result count without it. */
    public function active(): array
    {
        $out = [];
        foreach ($this->groups() as $g) {
            foreach ($g['opts'] as $o) {
                if ($o['on']) {
                    $out[] = [
                        'label' => $o['label'], 'lat' => $g['lat'],
                        'url' => $this->url($g['key'], $o['value'], false),
                        'without' => $this->all->filter(fn ($p) => $this->matches($p, $g['key'], $o['value']))->count(),
                    ];
                }
            }
        }

        return $out;
    }

    /** Filter groups for the sheet: brand, protocol, then the category's own fields. */
    public function groups(): array
    {
        $count = fn (string $g, string $v) => $this->all->filter(
            fn ($p) => in_array($v, $this->keys($p)[$g] ?? [], true) && $this->matches($p, $g)
        )->count();
        $opt = fn (string $g, string $v, string $label, ?string $swatch = null) => [
            'value' => $v, 'label' => $label, 'swatch' => $swatch,
            'on' => in_array($v, $this->sel[$g] ?? [], true), 'n' => $count($g, $v),
        ];

        $groups = [];
        $brands = $this->all->pluck('brand')->unique('id')->sortBy('sort');
        $groups[] = ['key' => 'b', 'title' => 'برند', 'style' => 'list', 'lat' => true, 'special' => false,
            'opts' => $brands->map(fn ($b) => $opt('b', $b->slug, $b->name))->values()->all()];

        $protos = $this->all->flatMap(fn ($p) => $p->publicProtocols())->unique('id')->sortBy('sort');
        if ($protos->isNotEmpty()) {
            $groups[] = ['key' => 'p', 'title' => 'پروتکل', 'style' => 'chips', 'lat' => true, 'special' => false,
                'opts' => $protos->map(fn ($x) => $opt('p', $x->code, $x->name))->values()->all()];
        }

        foreach ($this->fields as $f) {
            $g = 'f.'.$f->key;
            $present = $this->all->flatMap(fn ($p) => $this->keys($p)[$g])->unique()->values();
            $opts = match ($f->type) {
                'select', 'multi' => $f->options->filter(fn ($o) => $present->contains($o->value))
                    ->map(fn ($o) => $opt($g, $o->value, $o->label, $o->swatch)),
                'number' => $present->sortBy(fn ($v) => (float) $v)
                    ->map(fn ($v) => $opt($g, $v, Fa::num((float) $v).($f->unit ? ' '.$f->unit : ''))),
                'bool' => collect(['1' => 'بله', '0' => 'خیر'])->filter(fn ($l, $v) => $present->contains((string) $v))
                    ->map(fn ($l, $v) => $opt($g, (string) $v, $l)),
                default => collect(),
            };
            if ($opts->isNotEmpty()) {
                $groups[] = ['key' => $g, 'title' => $f->label, 'style' => 'chips', 'lat' => false, 'special' => true,
                    'opts' => $opts->values()->all()];
            }
        }

        return $groups;
    }

    /** Facet matrix for the sheet's live count: one entry per product. */
    public function matrix(): array
    {
        return $this->all->map(fn ($p) => $this->keys($p))->values()->all();
    }

    /** Field name in the GET form for a group. */
    public static function input(string $group): string
    {
        return str_starts_with($group, 'f.') ? 'f['.substr($group, 2).'][]' : $group.'[]';
    }

    /** Current URL with one value added or removed. */
    public function url(string $group, string $value, bool $on): string
    {
        $sel = $this->sel;
        $sel[$group] = $on ? array_values(array_unique([...($sel[$group] ?? []), $value]))
            : array_values(array_diff($sel[$group] ?? [], [$value]));

        return $this->build(array_filter($sel));
    }

    public function build(array $sel, ?string $sort = null): string
    {
        $q = [];
        foreach ($sel as $g => $vals) {
            if (str_starts_with($g, 'f.')) {
                $q['f'][substr($g, 2)] = $vals;
            } else {
                $q[$g] = $vals;
            }
        }
        $sort ??= $this->sort;
        if ($sort !== 'rec') {
            $q['s'] = $sort;
        }

        return route('category', $this->category).($q ? '?'.http_build_query($q) : '');
    }

    public function sortUrl(string $sort): string
    {
        return $this->build($this->sel, $sort);
    }
}
