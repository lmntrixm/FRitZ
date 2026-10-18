{{-- Category list (M-List, M-Filter, M-States, Main.dc.html list): filter + sort bar, active chips, grid, compare bar, filter sheet. --}}
@php use App\Support\CatalogFilter; use App\Support\Fa; @endphp
<x-layouts.site :title="$category->name" :sub="$sub" wide>

  <div x-data="filterSheet(@js($filter->matrix()), @js(collect($groups)->mapWithKeys(fn ($g) => [$g['key'] => collect($g['opts'])->where('on', true)->pluck('value')->values()])), @js($openSheet))">
    <div class="phd">
      <span class="phd-ic"><x-icon :i="$category->icon ?: 'box'" s="22" /></span>
      <div style="min-width:0"><h1 class="phd-t">{{ $category->name }}</h1><p class="t-s mut">{{ $sub }}@if ($category->parent) · {{ $category->parent->name }}@endif</p></div>
    </div>
    <div class="vbar">
      <div class="vtabs" role="group" aria-label="نوع نمایش">
        <button type="button" class="vtab" :class="$store.view.cols === 2 && 'on'" :aria-pressed="$store.view.cols === 2" @click="$store.view.set(2)"><x-icon i="grid2" s="16" />کارت</button>
        <button type="button" class="vtab" :class="$store.view.cols === 1 && 'on'" :aria-pressed="$store.view.cols === 1" @click="$store.view.set(1)"><x-icon i="menu" s="16" />ردیف</button>
      </div>
      <div style="flex:1"></div>
      <button class="selbtn" type="button" @click="sortOpen = true" aria-haspopup="dialog"><x-icon i="sort" s="16" />{{ ['rec' => 'پیشنهادی', 'new' => 'جدیدترین', 'name' => 'نام'][$filter->sort] }}</button>
      <button @class(['selbtn', 'on' => $activeN]) type="button" @click="open = true" aria-label="{{ $activeN ? 'فیلتر، ' . Fa::digits($activeN) . ' فعال' : 'فیلتر' }}" aria-haspopup="dialog">
        <x-icon i="filter" s="16" />فیلتر@if ($activeN)<span class="n">{{ Fa::digits($activeN) }}</span>@endif
      </button>
    </div>

    @if ($active)
      <div class="chips-x" style="padding-top:12px;padding-bottom:2px">
        @foreach ($active as $a)
          <span class="chip on rm"><span @class(['lat' => $a['lat']])>{{ $a['label'] }}</span><a class="x" href="{{ $a['url'] }}" aria-label="حذف فیلتر {{ $a['label'] }}"><x-icon i="x" s="14" sw="2" /></a></span>
        @endforeach
        <a class="chip" href="{{ $filter->build([]) }}" style="background:transparent;color:var(--acT);font-weight:800">پاک کردن همه</a>
      </div>
    @endif

    @if ($results->isNotEmpty())
      <div class="list-wrap g2" :class="{ g2: $store.view.cols === 2, g1: $store.view.cols === 1 }">
        @foreach ($results as $p)
          <x-pcard :p="$p" cmp s="120" />
        @endforeach
      </div>
    @elseif ($active)
      <div class="empty" style="padding-top:64px">
        <span class="ico"><x-icon i="noResult" s="32" /></span>
        <h2 class="t-h2">محصولی با این ترکیب پیدا نشد</h2>
        <p class="t-s mut">با حذف یکی از فیلترها نتیجه می‌گیرید:</p>
        <div style="display:flex;flex-direction:column;gap:8px;width:100%;margin-top:8px">
          @foreach (collect($active)->where('without', '>', 0) as $a)
            <a class="btn btn-s btn-w" href="{{ $a['url'] }}" style="justify-content:space-between"><span>بدون «<span @class(['lat' => $a['lat']])>{{ $a['label'] }}</span>»</span><span class="t-c mut3">{{ Fa::digits($a['without']) }} نتیجه</span></a>
          @endforeach
          <a class="btn btn-g btn-w" href="{{ $filter->build([]) }}" style="color:var(--acT)">پاک کردن همه فیلترها</a>
        </div>
      </div>
    @else
      <div class="empty" style="padding-top:64px">
        <span class="ico"><x-icon i="{{ $category->icon ?: 'box' }}" s="32" /></span>
        <h2 class="t-h2">هنوز محصولی در این دسته منتشر نشده</h2>
        <p class="t-s mut">برای تجهیزات این دسته از کارشناسان FRitZ بپرسید.</p>
        <button class="btn btn-p" type="button" style="margin-top:8px" @click="$store.inq.show()">لیست استعلام</button>
      </div>
    @endif

    {{-- compare bar --}}
    <div class="cbar" role="region" aria-label="نوار مقایسه" data-cmpbar x-show="$store.cmp.items.length" x-cloak>
      <template x-for="i in [0, 1, 2]" :key="i">
        <span class="slot" :class="!$store.cmp.items[i] && 'empty'">
          <template x-if="$store.cmp.items[i] && $store.cmp.items[i].img"><img :src="$store.cmp.items[i].img" alt=""></template>
          <template x-if="$store.cmp.items[i] && !$store.cmp.items[i].img"><svg class="art" width="46" height="46" viewBox="0 0 200 200" aria-hidden="true"><use :href="'#art-' + $store.cmp.items[i].art"/></svg></template>
        </span>
      </template>
      <span class="t-c" style="font-weight:700;padding-inline:4px" x-text="FA($store.cmp.items.length) + ' از ۳'"></span>
      <a class="btn btn-p btn-sm" :href="$store.cmp.url">مقایسه</a>
    </div>

    {{-- filter sheet --}}
    <div x-show="open" x-cloak @keydown.escape.window="open = false">
      <div class="scrim" @click="open = false" x-transition:enter="fade-enter" x-transition:enter-start="fade-from" x-transition:leave="fade-leave" x-transition:leave-end="fade-to-out"></div>
      <form class="sheet tall" role="dialog" aria-modal="true" aria-labelledby="flt-h" method="get" action="{{ route('category', $category) }}"
            x-trap.noscroll.inert="open" x-transition:enter="rise-enter" x-transition:enter-start="rise-from" x-transition:leave="rise-leave" x-transition:leave-end="rise-to-out">
        <span class="grab"></span>
        <div class="sheet-h"><h2 class="t-h2" id="flt-h">فیلتر</h2><button class="ib" type="button" aria-label="بستن" @click="open = false"><x-icon i="x" /></button></div>
        <div class="sheet-b">
          <div class="fgrp">
            <div class="fgrp-h">دسته</div>
            <div class="chips">
              @foreach ($cats as $c)
                <a @class(['chip', 'on' => $c->id === $category->id]) href="{{ route('category', $c) }}?sheet=1" @if($c->id === $category->id) aria-current="page" @endif>{{ $c->name }}</a>
              @endforeach
            </div>
          </div>
          @foreach ($groups as $g)
            <fieldset class="fgrp" style="border:0;margin:0;padding-inline:0">
              <legend class="fgrp-h" style="width:100%;padding:0">{{ $g['title'] }}
                @if ($g['special'])<span class="tag-new"><x-icon i="layers" s="14" />ویژه {{ $category->name }}</span>@endif
              </legend>
              @if ($g['style'] === 'list')
                <div>
                  @foreach ($g['opts'] as $o)
                    <label class="opt"><input type="checkbox" class="cbx" name="{{ CatalogFilter::input($g['key']) }}" value="{{ $o['value'] }}" x-model="sel['{{ $g['key'] }}']"><span class="lat" style="font-weight:600">{{ $o['label'] }}</span><span class="n">{{ Fa::digits($o['n']) }}</span></label>
                  @endforeach
                </div>
              @else
                <div class="chips">
                  @foreach ($g['opts'] as $o)
                    <label class="chip" :class="sel['{{ $g['key'] }}'].includes(@js($o['value'])) && 'on'">
                      <input type="checkbox" class="sr" name="{{ CatalogFilter::input($g['key']) }}" value="{{ $o['value'] }}" x-model="sel['{{ $g['key'] }}']">
                      @if ($o['swatch'])<span class="swc" style="background:{{ $o['swatch'] }}"></span>@endif
                      <span @class(['lat' => $g['lat']])>{{ $o['label'] }}</span>
                    </label>
                  @endforeach
                </div>
              @endif
            </fieldset>
          @endforeach
          @if ($filter->sort !== 'rec')<input type="hidden" name="s" value="{{ $filter->sort }}">@endif
        </div>
        <div class="sheet-f">
          <button class="btn btn-s" type="button" @click="clear()">پاک کردن</button>
          <button class="btn btn-p" type="submit" style="flex:1" :disabled="count === 0" x-text="count === 0 ? 'بدون نتیجه' : 'نمایش ' + FA(count) + ' نتیجه'">نمایش نتایج</button>
        </div>
      </form>
    </div>

    {{-- sort sheet --}}
    <div x-show="sortOpen" x-cloak @keydown.escape.window="sortOpen = false">
      <div class="scrim" @click="sortOpen = false"></div>
      <div class="sheet" role="dialog" aria-modal="true" aria-labelledby="srt-h" x-trap.noscroll.inert="sortOpen"
           x-transition:enter="rise-enter" x-transition:enter-start="rise-from" x-transition:leave="rise-leave" x-transition:leave-end="rise-to-out">
        <span class="grab"></span>
        <div class="sheet-h"><h2 class="t-h2" id="srt-h">مرتب‌سازی</h2><button class="ib" type="button" aria-label="بستن" @click="sortOpen = false"><x-icon i="x" /></button></div>
        <div class="sheet-b">
          @foreach (['rec' => 'پیشنهادی', 'new' => 'جدیدترین', 'name' => 'نام'] as $k => $l)
            <a class="opt" href="{{ $filter->sortUrl($k) }}" @if($filter->sort === $k) aria-current="true" @endif>
              <span class="rdo" style="display:inline-grid" aria-hidden="true">@if($filter->sort === $k)<span style="width:8px;height:8px;border-radius:50%;background:var(--bg)"></span>@endif</span>{{ $l }}
            </a>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  @push('head')
  <script>
    /* Filter sheet state: live result count from the facet matrix, before the form is sent. */
    function filterSheet(matrix, sel, startOpen) {
      return {
        open: startOpen, sortOpen: false, sel,
        get count() {
          return matrix.filter((k) => Object.entries(this.sel).every(([g, v]) => !v.length || v.some((x) => (k[g] || []).includes(x)))).length;
        },
        clear() { Object.keys(this.sel).forEach((g) => { this.sel[g] = []; }); },
      };
    }
  </script>
  @endpush
</x-layouts.site>
