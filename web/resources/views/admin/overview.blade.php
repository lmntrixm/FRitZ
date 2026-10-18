{{-- Overview (A-Overview). All figures are live; empty states say so instead of drawing fake data. --}}
@php
  use App\Support\Fa;
  $max = max(10, (int) ceil(max($series) / 10) * 10);
  $pts = collect($series)->map(fn ($v, $i) => [40 + $i * 50, round(190 - $v / $max * 150, 1)]);
  $last = $pts->last();
  $acts = [
    'settings.update' => ['i' => 'settings', 'ic' => 'ic mod', 'what' => fn ($c) => 'تنظیمات را تغییر داد: ' . implode('، ', $c['fields'] ?? [])],
    'setup' => ['i' => 'user', 'ic' => 'ic add', 'what' => fn ($c) => 'پنل را راه‌اندازی کرد'],
  ];
@endphp
<x-layouts.admin title="نمای کلی" active="overview" :sub="$today . ' · داده‌های ۱۲ هفته اخیر'">
  <div class="stats">
    <div class="stat"><span class="l"><x-icon i="box" s="16" />محصولات</span><span class="v">{{ Fa::digits($products['total']) }}</span>
      <span class="s"><span style="color:var(--okT);font-weight:700">{{ Fa::digits($products['published']) }} منتشرشده</span> · {{ Fa::digits($products['draft']) }} پیش‌نویس</span></div>
    <div class="stat"><span class="l"><x-icon i="chat" s="16" />استعلام‌های این هفته</span><span class="v">{{ Fa::digits($week['now']) }}</span>
      <span class="s">
        @if ($week['delta'] !== null)
          <span class="delta" @if($week['delta'] < 0) style="color:var(--err)" @endif><x-icon :i="$week['delta'] < 0 ? 'arrowDown' : 'arrowUp'" s="12" sw="2.2" />{{ Fa::digits(abs($week['delta'])) }}٪</span> نسبت به هفته قبل ({{ Fa::digits($week['prev']) }})
        @else
          هفته قبل: {{ Fa::digits($week['prev']) }}
        @endif
      </span></div>
    <div class="stat"><span class="l"><x-icon i="chart" s="16" />پراستعلام‌ترین محصول</span>
      @if ($top->isNotEmpty())
        <span style="font-size:16px;font-weight:800;line-height:1.6">{{ $top[0]->n }}</span><span class="s">{{ Fa::digits($top[0]->v) }} بار در ۱۲ هفته · <span class="code">{{ $top[0]->c }}</span></span>
      @else
        <span style="font-size:16px;font-weight:800;line-height:1.6">هنوز استعلامی نیامده</span><span class="s">پس از اولین ارسال در واتساپ اینجا دیده می‌شود</span>
      @endif
    </div>
    <div class="stat"><span class="l"><x-icon i="upload" s="16" />آخرین به‌روزرسانی</span>
      @if ($lastUpdate)
        <span style="font-size:16px;font-weight:800;line-height:1.6">{{ Fa::date(\Illuminate\Support\Carbon::parse($lastUpdate->applied_at), 'd MMMM y') }}</span><span class="s">{{ Fa::digits($lastUpdate->added) }} جدید · {{ Fa::digits($lastUpdate->modified) }} ویرایش</span>
      @else
        <span style="font-size:16px;font-weight:800;line-height:1.6">هنوز انجام نشده</span><span class="s">به‌روزرسانی با فایل در مرحله بعد فعال می‌شود</span>
      @endif
    </div>
  </div>

  <div class="g2a">
    <section class="panel">
      <div class="panel-h"><h2>تعداد استعلام در هفته</h2><span class="t-c mut3">مجموع {{ Fa::digits($seriesTotal) }} استعلام</span></div>
      <div class="panel-b">
        <svg class="chart" viewBox="0 0 600 230" width="100%" role="img" aria-label="نمودار خطی تعداد استعلام هفتگی در ۱۲ هفته اخیر؛ این هفته {{ Fa::digits($week['now']) }}">
          <line x1="40" y1="190" x2="590" y2="190" style="stroke:var(--bd2)"/>
          <line x1="40" y1="115" x2="590" y2="115" style="stroke:var(--grid)" stroke-dasharray="3 4"/>
          <line x1="40" y1="40" x2="590" y2="40" style="stroke:var(--grid)" stroke-dasharray="3 4"/>
          <text x="30" y="194" text-anchor="end">۰</text>
          <text x="30" y="119" text-anchor="end">{{ Fa::digits($max / 2) }}</text>
          <text x="30" y="44" text-anchor="end">{{ Fa::digits($max) }}</text>
          <polyline points="{{ $pts->map(fn ($p) => implode(',', $p))->join(' ') }}" fill="none" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" style="stroke:var(--c2)"/>
          <circle cx="{{ $last[0] }}" cy="{{ $last[1] }}" r="5" style="fill:var(--c2);stroke:var(--sf)" stroke-width="2"/>
          <text x="40" y="214" text-anchor="middle">۱۲ هفته پیش</text>
          <text x="590" y="214" text-anchor="middle">این هفته</text>
        </svg>
      </div>
    </section>
    <section class="panel">
      <div class="panel-h"><h2>۱۰ محصول پراستعلام</h2><span class="t-c mut3">تعداد استعلام · ۱۲ هفته</span></div>
      <div class="panel-b" style="display:flex;flex-direction:column;gap:6px">
        @forelse ($top as $t)
          <div class="hbar"><span style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;font-weight:600">{{ $t->n }}</span><span class="tr"><i style="width:{{ round($t->v / $top[0]->v * 100) }}%;background:var(--c2)"></i></span><span class="num" style="font-weight:800">{{ Fa::digits($t->v) }}</span></div>
        @empty
          <p class="t-s mut">هنوز استعلامی ثبت نشده است.</p>
        @endforelse
      </div>
    </section>
  </div>

  <div class="g2a">
    @foreach ([['سهم برندها', 'از ' . Fa::digits($products['total']) . ' محصول', $brands, Fa::digits($brandCount), 'برند'], ['سهم پروتکل‌ها', 'هر محصول می‌تواند چند پروتکل داشته باشد', $protocols, Fa::digits($protoActive), 'پروتکل فعال']] as [$h, $note, $rows, $big, $small])
      <section class="panel">
        <div class="panel-h"><h2>{{ $h }}</h2><span class="t-c mut3">{{ $note }}</span></div>
        <div class="panel-b" style="display:flex;align-items:center;gap:28px;flex-wrap:wrap">
          <svg viewBox="0 0 160 160" width="160" height="160" role="img" aria-label="نمودار حلقوی {{ $h }}">
            <g transform="rotate(-90 80 80)">
              @foreach ($rows as $r)<circle cx="80" cy="80" r="60" fill="none" stroke-width="22" stroke-dasharray="{{ $r['dash'] }}" stroke-dashoffset="{{ $r['off'] }}" style="stroke:{{ $r['color'] }}"/>@endforeach
            </g>
            <text x="80" y="78" text-anchor="middle" style="font-size:22px;font-weight:800;fill:var(--tx)">{{ $big }}</text>
            <text x="80" y="98" text-anchor="middle" style="font-size:11px;fill:var(--tx3)">{{ $small }}</text>
          </svg>
          <div style="display:flex;flex-direction:column;gap:8px;flex:1;min-width:200px">
            @foreach ($rows as $r)
              <div style="display:flex;align-items:center;gap:8px;font-size:13px"><i style="width:10px;height:10px;border-radius:3px;background:{{ $r['color'] }};display:inline-block"></i><span @class(['lat' => $r['lat']]) style="flex:1;font-weight:600">{{ $r['n'] }}</span><b class="num">{{ $r['pct'] }}</b><span class="t-c mut3" style="width:64px">{{ Fa::digits($r['v']) }} محصول</span></div>
            @endforeach
          </div>
        </div>
      </section>
    @endforeach
  </div>

  <div class="g2a">
    <section class="panel">
      <div class="panel-h"><h2 style="display:flex;align-items:center;gap:8px"><x-icon i="alert" s="18" />نیاز به توجه</h2><span class="t-c mut3">{{ Fa::digits(collect($attention)->sum('n')) }} مورد</span></div>
      <div>
        @forelse ($attention as $a)
          <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--line)">
            <span class="thumb"><x-icon :i="$a['i']" s="18" /></span>
            <span style="flex:1;display:flex;flex-direction:column"><b style="font-size:14px">{{ $a['l'] }}</b><span class="t-c mut3">{{ $a['s'] }}</span></span>
            <span class="{{ $a['st'] }}">{{ Fa::digits($a['n']) }}</span>
          </div>
        @empty
          <p class="t-s mut" style="padding:12px 18px 22px">همه محصولات کامل‌اند.</p>
        @endforelse
      </div>
    </section>
    <section class="panel">
      <div class="panel-h"><h2>فعالیت اخیر</h2></div>
      <div class="tl" style="padding:4px 18px">
        @forelse ($activity as $a)
          @php $def = $acts[$a->action] ?? ['i' => 'activity', 'ic' => 'ic', 'what' => fn ($c) => $a->action]; @endphp
          <div class="tli" style="border-bottom:1px solid var(--line)"><span class="{{ $def['ic'] }}"><x-icon :i="$def['i']" s="16" /></span>
            <span style="display:flex;flex-direction:column;gap:2px"><span class="t-s"><b>{{ $a->who ?? 'سیستم' }}</b> {{ ($def['what'])(json_decode($a->changes ?? '[]', true) ?: []) }}</span><span class="t-c mut3">{{ Fa::ago(\Illuminate\Support\Carbon::parse($a->created_at)) }}</span></span></div>
        @empty
          <p class="t-s mut" style="padding:8px 0 18px">هنوز فعالیتی ثبت نشده است.</p>
        @endforelse
      </div>
    </section>
  </div>
</x-layouts.admin>
