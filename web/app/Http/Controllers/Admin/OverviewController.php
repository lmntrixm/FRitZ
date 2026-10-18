<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CategoryAttribute;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Protocol;
use App\Support\Fa;
use Illuminate\Support\Facades\DB;

/** Overview (A-Overview): every number comes from the database; nothing is decorative. */
class OverviewController extends Controller
{
    private const WEEKS = 12;

    public function __invoke()
    {
        $since = now()->subWeeks(self::WEEKS);
        $inq = Inquiry::query()->where('created_at', '>=', $since)->get(['id', 'created_at']);
        $weeks = array_fill(0, self::WEEKS, 0); // oldest → this week
        foreach ($inq as $i) {
            $ago = intdiv((int) $i->created_at->diffInDays(now()), 7);
            if ($ago < self::WEEKS) {
                $weeks[self::WEEKS - 1 - $ago]++;
            }
        }
        $thisWeek = $weeks[self::WEEKS - 1];
        $lastWeek = $weeks[self::WEEKS - 2];

        $top = DB::table('inquiry_items')->join('inquiries', 'inquiries.id', '=', 'inquiry_items.inquiry_id')
            ->where('inquiries.created_at', '>=', $since)
            ->selectRaw('name_snapshot as n, code_snapshot as c, count(*) as v')
            ->groupBy('name_snapshot', 'code_snapshot')->orderByDesc('v')->limit(10)->get();

        $total = Product::count();
        $brandCounts = Brand::query()->withCount('products')->orderByDesc('products_count')->get()->where('products_count', '>', 0)->values();
        $protoCounts = Protocol::active()->withCount('products')->get();
        $noProto = Product::query()->whereDoesntHave('protocols', fn ($q) => $q->where('is_active', true))->count();

        return view('admin.overview', [
            'today' => Fa::date(now()),
            'products' => ['total' => $total, 'published' => Product::where('status', 'published')->count(), 'draft' => Product::where('status', 'draft')->count()],
            'week' => ['now' => $thisWeek, 'prev' => $lastWeek,
                'delta' => $lastWeek > 0 ? (int) round(($thisWeek - $lastWeek) / $lastWeek * 100) : null],
            'top' => $top,
            'lastUpdate' => DB::table('updates')->where('status', 'applied')->latest('applied_at')->first(),
            'series' => $weeks,
            'seriesTotal' => array_sum($weeks),
            'brands' => $this->share($brandCounts->take(4)->map(fn ($b) => ['n' => $b->name, 'v' => $b->products_count, 'lat' => true])
                ->push(['n' => 'سایر ('.Fa::digits(max(0, $brandCounts->count() - 4)).' برند)', 'v' => $brandCounts->slice(4)->sum('products_count'), 'lat' => false])
                ->filter(fn ($r) => $r['v'] > 0)->values()->all()),
            'brandCount' => $brandCounts->count(),
            'protocols' => $this->share($protoCounts->map(fn ($p) => ['n' => $p->name, 'v' => $p->products_count, 'lat' => true])
                ->push(['n' => 'بدون پروتکل', 'v' => $noProto, 'lat' => false])->filter(fn ($r) => $r['v'] > 0)->values()->all()),
            'protoActive' => $protoCounts->count(),
            'attention' => $this->attention(),
            'activity' => DB::table('activity_log')->leftJoin('users', 'users.id', '=', 'activity_log.user_id')
                ->select('activity_log.*', 'users.name as who')->latest('activity_log.id')->limit(6)->get(),
        ]);
    }

    /** Donut slices: dash length/offset on a r=60 circle, plus legend rows. */
    private function share(array $rows): array
    {
        $c = 2 * M_PI * 60;
        $sum = array_sum(array_column($rows, 'v')) ?: 1;
        $acc = 0;
        $colors = ['var(--c1)', 'var(--c2)', 'var(--c3)', 'var(--c4)', 'var(--bd2)'];
        foreach ($rows as $i => &$r) {
            $len = $r['v'] / $sum * $c;
            $r['dash'] = round(max($len - 3, 0), 1).' '.round($c, 1);
            $r['off'] = round(-$acc, 1);
            $r['pct'] = Fa::digits((int) round($r['v'] / $sum * 100)).'٪';
            $r['color'] = $i === count($rows) - 1 && ! $r['lat'] ? 'var(--bd2)' : $colors[$i % 4];
            $acc += $len;
        }

        return $rows;
    }

    private function attention(): array
    {
        $required = CategoryAttribute::query()->where('required_to_publish', true)->get(['id', 'category_id']);
        $incomplete = Product::query()->get(['id', 'category_id'])->filter(function ($p) use ($required) {
            $need = $required->where('category_id', $p->category_id)->pluck('id');

            return $need->isNotEmpty() && DB::table('product_values')->where('product_id', $p->id)->whereIn('attribute_id', $need)->distinct()->count('attribute_id') < $need->count();
        })->count();

        return array_values(array_filter([
            ['i' => 'image', 'l' => 'بدون عکس', 's' => 'در کاتالوگ عمومی با تصویر جای‌نگهدار نمایش داده می‌شوند', 'st' => 'st er',
                'n' => Product::whereDoesntHave('media', fn ($q) => $q->where('product_media.role', 'image'))->count()],
            ['i' => 'doc', 'l' => 'بدون دیتاشیت', 's' => 'دکمه «دیتاشیت» در صفحه محصول پنهان است', 'st' => 'st wn',
                'n' => Product::whereDoesntHave('media', fn ($q) => $q->where('product_media.role', 'datasheet'))->count()],
            ['i' => 'tag', 'l' => 'بدون پروتکل فعال', 's' => 'در فیلتر پروتکل و راهنمای انتخاب دیده نمی‌شوند', 'st' => 'st wn',
                'n' => Product::whereDoesntHave('protocols', fn ($q) => $q->where('is_active', true))->count()],
            ['i' => 'table', 'l' => 'مشخصات ناقص', 's' => 'فیلدهای لازم دسته خالی است', 'st' => 'st wn', 'n' => $incomplete],
        ], fn ($r) => $r['n'] > 0));
    }
}
