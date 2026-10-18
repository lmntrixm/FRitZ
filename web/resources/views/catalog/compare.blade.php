{{-- Compare (M-Compare): up to 3 products, sticky label column, rows that differ highlighted, «فقط تفاوت‌ها», add all to inquiry. --}}
@php use App\Support\Fa; $n = $products->count(); @endphp
<x-layouts.site title="مقایسه" :stick="$n > 0">
  <div x-data="{ only: false }" x-init="if (!location.search.includes('ids=') && $store.cmp.items.length) location.replace($store.cmp.url)">
    <header class="hdr">
      <b style="font-size:16px;font-weight:800;padding-inline-start:8px">مقایسه {{ Fa::digits($n) }} محصول</b>
      <div class="sp"></div>
      @if ($n > 1)
        <label class="opt" style="font-size:13px;font-weight:700;padding-inline-end:8px"><input type="checkbox" class="cbx" x-model="only">فقط تفاوت‌ها</label>
      @endif
    </header>

    @if ($n === 0)
      <div class="empty" style="padding-top:96px">
        <span class="ico"><x-icon i="compare" s="32" /></span>
        <h2 class="t-h2">محصولی برای مقایسه انتخاب نشده</h2>
        <p class="t-s mut">در لیست محصولات، «مقایسه» را روی حداکثر ۳ محصول بزنید.</p>
        <a class="btn btn-p" href="{{ route('home') }}#cats" style="margin-top:8px">مشاهده محصولات</a>
      </div>
    @else
      <div class="ctable" style="--n: {{ $n }}">
        <div style="width:max-content;min-width:100%;padding-bottom:24px">
          <div class="crow chead">
            <div><span class="t-c mut3" style="font-weight:700">ویژگی</span></div>
            @foreach ($products as $p)
              <div>
                <button class="ib" type="button" style="position:absolute;top:4px;left:0;width:36px;height:36px;z-index:1" aria-label="حذف {{ $p->name }} از مقایسه"
                        @click="$store.cmp.remove({{ $p->id }}); location.href = '{{ route('compare') }}?ids={{ $products->pluck('id')->reject(fn ($id) => $id === $p->id)->join(',') }}'"><x-icon i="x" s="16" /></button>
                <a class="ph" href="{{ route('product', $p) }}" style="height:92px"><x-pimg :p="$p" s="84" /></a>
                <b style="font-size:13px;line-height:1.55;min-height:40px">{{ $p->name }}</b>
                <span class="code">{{ $p->model_code }}</span>
                <button type="button" class="pc-add" style="min-height:40px;font-size:12px" :class="$store.inq.has({{ $p->id }}) && 'in'" @click="$store.inq.toggle(@js($p->card()))"
                        x-text="$store.inq.has({{ $p->id }}) ? 'در لیست' : 'افزودن'">افزودن</button>
              </div>
            @endforeach
          </div>
          @foreach ($rows as $r)
            <div @class(['crow', 'diff' => $r['diff'] && $n > 1]) @if(! $r['diff']) x-show="!only" @endif>
              <div>{{ $r['k'] }}</div>
              @foreach ($r['v'] as $v)<div><span @class(['lat' => $r['lat']])>{{ $v }}</span></div>@endforeach
            </div>
          @endforeach
        </div>
      </div>
      <div class="stick">
        <button class="btn btn-p btn-lg btn-w" type="button" @click="$store.inq.addAll(@js($products->map->card()->values()))"><x-icon i="plus" s="20" sw="2" />افزودن هر {{ Fa::digits($n) }} به لیست استعلام</button>
      </div>
    @endif
  </div>
</x-layouts.site>
