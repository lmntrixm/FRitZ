{{-- Home (M-Home, Main.dc.html home): sticky header + search, hero product, categories, brands, featured, finder, footer. --}}
<x-layouts.site>
  <x-slot:header>
    <div class="stickytop" x-stuck>
      <x-hdr />
      <div class="sbar" role="search">
        <a class="sfield" href="{{ route('search') }}"><x-icon i="search" s="20" /><span>نام محصول یا کد مدل</span></a>
        <a class="btn btn-s btn-sm" href="{{ $cats->isNotEmpty() ? route('category', $cats->first()) . '?sheet=1' : '#cats' }}" aria-label="فیلتر محصولات"><x-icon i="filter" s="18" />فیلتر</a>
      </div>
    </div>
  </x-slot:header>

  <section style="padding:8px 0 0">
    @if ($hero)
      <a href="{{ route('product', $hero) }}" style="display:block;color:inherit" aria-label="{{ $hero->name }}">
        <figure class="ph hero-ph">
          <x-pimg :p="$hero" s="250" eager />
          <figcaption style="position:absolute;bottom:16px;right:20px;left:20px;display:flex;justify-content:space-between;gap:12px;font-size:13px">
            <span><span class="brand">{{ $hero->brand->name }}</span> · <span class="lat">{{ $hero->model_code }}</span></span>
            <span class="lat mut3">{{ $hero->publicProtocols()->pluck('name')->join(' / ') }}</span>
          </figcaption>
        </figure>
      </a>
    @endif
    <div style="padding:28px 20px 0">
      <h1 class="t-dsp">تجهیز دقیق، برای خانه‌ای که فکر می‌کند.</h1>
      <p class="t-b mut" style="margin-top:14px">{{ $totalLabel }} محصول از {{ $brandCount }} برند؛ مشخصات را بخوانید، مقایسه کنید و یکجا استعلام بگیرید.</p>
      <a class="btn btn-p btn-lg btn-w" href="{{ route('finder') }}" style="margin-top:28px">محصول مناسبم را پیدا کن<x-icon i="arrowNext" s="20" /></a>
    </div>
  </section>

  <section class="sec" style="padding-top:96px" id="cats" x-data="{ all: false }">
    <h2 class="t-h1">از نیاز پروژه شروع کنید.</h2>
    <p class="t-s mut" style="margin:8px 0 24px">{{ \App\Support\Fa::digits($cats->count()) }} دسته، هرکدام با فیلترهای مخصوص خودش.</p>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:2px">
      @foreach ($cats as $i => $c)
        <a class="cat" href="{{ route('category', $c) }}" style="min-height:76px;padding:0 16px" @if($i >= 6) x-show="all" x-cloak @endif>
          <span style="display:block"><b>{{ $c->name }}</b><span>{{ \App\Support\Fa::digits($c->products_count) }} محصول</span></span>
        </a>
      @endforeach
    </div>
    @if ($cats->count() > 6)
      <button class="btn btn-g btn-w" type="button" style="margin-top:8px" @click="all = !all" :aria-expanded="all">
        <span x-text="all ? 'نمایش کمتر' : 'مشاهده همه {{ \App\Support\Fa::digits($cats->count()) }} دسته'">مشاهده همه {{ \App\Support\Fa::digits($cats->count()) }} دسته</span>
        <span x-show="!all"><x-icon i="chevDown" s="18" /></span><span x-show="all" x-cloak><x-icon i="chevUp" s="18" /></span>
      </button>
    @endif
  </section>

  <section class="sec" style="padding-top:96px">
    <h2 class="t-h1">{{ $brandCount }} برند، کنار هم و بی‌طرف.</h2>
    <nav aria-label="برندها" style="display:flex;flex-wrap:wrap;gap:0 22px;margin-top:18px;direction:ltr;justify-content:flex-end">
      @foreach ($brands as $b)
        <a href="{{ route('search', ['q' => $b->name]) }}" style="font-size:24px;line-height:1.7;font-weight:700;color:var(--tx3);min-height:44px;display:inline-flex;align-items:center">{{ $b->name }}</a>
      @endforeach
    </nav>
  </section>

  @if ($featured->isNotEmpty())
    <section class="sec" style="padding-top:96px">
      <div class="sec-h"><h2 class="t-h1">منتخب‌ها</h2><a class="more" href="#cats">همه محصولات</a></div>
    </section>
    <div class="hs">
      @foreach ($featured as $p)
        <x-pcard :p="$p" w="240" s="160" />
      @endforeach
    </div>
  @endif

  <section style="margin-top:96px;background:var(--sf2);padding:40px 20px">
    <h2 class="t-h1">نمی‌دانید از کجا شروع کنید؟</h2>
    <p class="t-s mut" style="margin-top:10px">در سه قدم نیاز، پروتکل و برند را انتخاب کنید؛ محصولات مناسب را با دلیل پیشنهاد می‌کنیم.</p>
    <a class="btn btn-d btn-w" href="{{ route('finder') }}" style="margin-top:24px">شروع راهنمای انتخاب<x-icon i="arrowNext" s="18" /></a>
  </section>

  <x-slot:footer><x-foot style="margin-top:0" /></x-slot:footer>
</x-layouts.site>
