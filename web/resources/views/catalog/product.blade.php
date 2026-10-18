{{-- Product (M-Product, Main.dc.html product): gallery, identity, compare toggle, specs, description, compatible, related, sticky inquiry bar. --}}
@php
  $card = $p->card();
  $images = $p->images();
  $sheet = $p->datasheet();
  $desc = $p->description ?: $p->name . ' از برند ' . $p->brand->name . '. برای مشخصات کامل و نقشه سیم‌بندی، دیتاشیت را دانلود کنید یا درباره سازگاری با پروژه‌تان از کارشناسان FRitZ بپرسید.';
@endphp
<x-layouts.site :title="$p->name . ' ' . $p->model_code" :description="\Illuminate\Support\Str::limit($desc, 155)" stick>
  <x-slot:header>
    <x-hdr title="جزئیات محصول" :sub="$p->category->name" :back="route('category', $p->category)" />
  </x-slot:header>

  @push('head')
  {{-- structured data without offers: no price is ever published --}}
  <script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p->name, 'sku' => $p->model_code, 'mpn' => $p->model_code,
    'brand' => ['@type' => 'Brand', 'name' => $p->brand->name], 'category' => $p->category->name, 'description' => $desc,
    'image' => $images->map(fn ($m) => $m->url())->all() ?: null,
  ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
  @endpush

  <div x-data="{ slide: 0, n: {{ max(1, $images->count()) }}, zoom: false, spec: true }">
    <div class="gal" @scroll.debounce.60ms="slide = Math.round(Math.abs($el.scrollLeft) / $el.clientWidth)" aria-label="تصاویر محصول" role="region">
      @forelse ($images as $i => $m)
        <div class="ph"><img src="{{ $m->url() }}" alt="{{ $m->alt ?: $p->name }}" @if($m->width) width="{{ $m->width }}" height="{{ $m->height }}" @endif loading="{{ $i ? 'lazy' : 'eager' }}"></div>
      @empty
        <div class="ph"><x-art :kind="$p->art()" s="250" :label="$p->name" /></div>
      @endforelse
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px 0;min-height:54px">
      <div class="dots" aria-hidden="true">
        @if ($images->count() > 1)
          @foreach ($images as $i => $m)<i :class="slide === {{ $i }} && 'on'" @class(['on' => $i === 0])></i>@endforeach
        @endif
      </div>
      @if ($images->isNotEmpty())
        <button class="btn btn-g btn-sm" type="button" @click="zoom = true" aria-label="بزرگ‌نمایی تصویر"><x-icon i="zoom" s="18" />بزرگ‌نمایی</button>
      @endif
    </div>

    <section style="padding:12px 16px 0;display:flex;flex-direction:column;gap:8px">
      <div style="display:flex;align-items:center;gap:10px"><span class="brand" style="color:var(--tx)">{{ $p->brand->name }}</span><span style="width:1px;height:12px;background:var(--bd2)"></span><span class="code">{{ $p->model_code }}</span></div>
      <h1 class="t-h1">{{ $p->name }}</h1>
      <div class="pbs">@foreach ($p->publicProtocols() as $x)<span class="pb">{{ $x->name }}</span>@endforeach</div>
      <label class="opt" style="margin-top:4px"><input type="checkbox" class="cbx" :checked="$store.cmp.has({{ $p->id }})" @change="$store.cmp.toggle(@js($card)); $event.target.checked = $store.cmp.has({{ $p->id }})">افزودن به مقایسه</label>
      <a class="more" x-show="$store.cmp.items.length > 1" x-cloak :href="$store.cmp.url" x-text="'مقایسه ' + FA($store.cmp.items.length) + ' محصول'"></a>
    </section>

    <section style="margin:8px 16px 0">
      <button class="acc" type="button" :aria-expanded="spec" aria-controls="spec" @click="spec = !spec">مشخصات فنی
        <span x-show="spec"><x-icon i="chevUp" s="20" /></span><span x-show="!spec" x-cloak><x-icon i="chevDown" s="20" /></span>
      </button>
      <table class="spec" id="spec" x-show="spec"><tbody>
        @foreach ($specs as $r)
          <tr><th scope="row">{{ $r['k'] }}</th><td><span @class(['lat' => $r['lat']])>{{ $r['v'] }}</span></td></tr>
        @endforeach
      </tbody></table>
    </section>

    <section style="margin:8px 16px 0;padding-top:16px">
      <h2 class="t-h3">توضیحات</h2>
      <p class="t-b mut" style="margin-top:6px">{{ $desc }}</p>
    </section>

    @if ($compatible->isNotEmpty())
      <section class="sec" style="padding-bottom:0"><div class="sec-h" style="margin-bottom:10px"><h2 class="t-h3">سازگار با</h2></div></section>
      <div class="hs">
        @foreach ($compatible as $c)
          <a class="mini" href="{{ route('product', $c) }}" style="color:inherit">
            <span class="ph"><x-pimg :p="$c" s="56" /></span>
            <span style="display:flex;flex-direction:column;gap:2px;min-width:0"><b style="font-size:13px;line-height:1.6">{{ $c->name }}</b><span class="code">{{ $c->model_code }}</span></span>
          </a>
        @endforeach
      </div>
    @endif

    @if ($related->isNotEmpty())
      <section class="sec" style="padding-bottom:0"><div class="sec-h" style="margin-bottom:10px"><h2 class="t-h3">محصولات مرتبط</h2></div></section>
      <div class="hs" style="padding-bottom:28px">
        @foreach ($related as $r)
          <x-pcard :p="$r" :add="false" w="180" s="110" />
        @endforeach
      </div>
    @endif

    <div class="stick">
      <button type="button" class="btn btn-lg btn-p" style="flex:1" :class="{ 'btn-ok': $store.inq.has({{ $p->id }}), 'btn-p': !$store.inq.has({{ $p->id }}) }" @click="$store.inq.toggle(@js($card))">
        <span x-show="!$store.inq.has({{ $p->id }})"><x-icon i="plus" s="20" sw="2" /></span>
        <span x-show="$store.inq.has({{ $p->id }})" x-cloak><x-icon i="check" s="20" sw="2" /></span>
        <span x-text="$store.inq.has({{ $p->id }}) ? 'در لیست استعلام · ' + FA($store.inq.qty({{ $p->id }})) + ' عدد' : 'افزودن به لیست استعلام'">افزودن به لیست استعلام</span>
      </button>
      @if ($sheet)
        <a class="btn btn-s btn-lg" style="padding:0 14px" href="{{ $sheet->url() }}" download><x-icon i="download" s="20" />دیتاشیت <span class="lat" style="font-size:12px">PDF</span></a>
      @endif
    </div>

    @if ($images->isNotEmpty())
      <div class="lightbox" x-show="zoom" x-cloak role="dialog" aria-modal="true" aria-label="تصویر بزرگ {{ $p->name }}" x-trap.noscroll.inert="zoom" @keydown.escape.window="zoom = false">
        <div class="hdr"><b style="font-size:16px;font-weight:800;flex:1;padding-inline:10px">{{ $p->name }}</b><button class="ib" type="button" aria-label="بستن" @click="zoom = false"><x-icon i="x" /></button></div>
        @foreach ($images as $i => $m)
          <div class="ph" x-show="slide === {{ $i }}"><img src="{{ $m->url() }}" alt="{{ $m->alt ?: $p->name }}" loading="lazy"></div>
        @endforeach
      </div>
    @endif
  </div>
</x-layouts.site>
