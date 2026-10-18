{{-- Home: a work-app home of widgets. Headline with search and the finder (the one lime action),
     every category with its count, featured products, brands, a line to a specialist. --}}
@php use App\Support\Fa; @endphp
<x-layouts.site wide>
  <div class="home">
    <section class="hwel">
      <h1 class="hwel-t">تجهیز دقیق، برای خانه‌ای که فکر می‌کند.</h1>
      <p class="t-b mut">{{ $totalLabel }} محصول از {{ $brandCount }} برند؛ مشخصات را بخوانید، مقایسه کنید و یکجا استعلام بگیرید.</p>
      <div class="hact">
        <button class="hsearch" type="button" @click="$dispatch('palette')"><x-icon i="search" s="20" /><span>نام محصول یا کد مدل</span><kbd class="kbd dsk">Ctrl K</kbd></button>
        <a class="btn btn-p" href="{{ route('finder') }}"><x-icon i="compass" s="20" />محصول مناسبم را پیدا کن</a>
      </div>
    </section>

    <div class="wgrid">
      <section class="wd wd-cats" id="cats" aria-labelledby="w-cats">
        <header class="wd-h"><h2 id="w-cats">دسته‌ها</h2><span class="tn">{{ Fa::digits($cats->count()) }} دسته، هرکدام با فیلترهای خودش</span></header>
        <div class="ctiles">
          @foreach ($cats as $c)
            <a @class(['ctile', 'none' => ! $c->products_count]) href="{{ route('category', $c) }}">
              <span class="ctile-ic"><x-icon :i="$c->icon ?: 'box'" s="20" /></span>
              <span class="ctile-t"><b>{{ $c->name }}</b><span>{{ $c->products_count ? Fa::digits($c->products_count) . ' محصول' : 'به‌زودی' }}</span></span>
            </a>
          @endforeach
        </div>
      </section>

      <section class="wd wd-find" aria-labelledby="w-find">
        <header class="wd-h"><h2 id="w-find">راهنمای انتخاب</h2><span class="tn">سه قدم</span></header>
        <ol class="steps">
          <li><span class="sn">۱</span><span><b>نیاز</b><span>روشنایی، پرده، دما، امنیت…</span></span></li>
          <li><span class="sn">۲</span><span><b>پروتکل</b><span class="lat">KNX · Zigbee · Modbus</span></span></li>
          <li><span class="sn">۳</span><span><b>برند</b><span>فقط برندهایی که برای انتخاب شما محصول دارند</span></span></li>
        </ol>
        <a class="btn btn-s btn-sm btn-w" href="{{ route('finder') }}">شروع راهنما<x-icon i="arrowNext" s="18" /></a>
      </section>

      <section class="wd wd-talk" aria-labelledby="w-talk">
        <header class="wd-h"><h2 id="w-talk">انتخاب برای پروژه</h2></header>
        <p class="t-s mut">نمی‌دانید کدام تجهیزات با پروژه‌تان سازگار است؟ با کارشناسان FRitZ گفت‌وگو کنید؛ از انتخاب تا نصب و راه‌اندازی همراهتان هستیم.</p>
        <a class="btn btn-s btn-sm btn-w" href="https://wa.me/{{ preg_replace('/\D+/', '', (string) \App\Models\Setting::get('whatsapp_number', '')) }}" target="_blank" rel="noopener"><x-icon i="chat" s="18" />گفت‌وگو در واتساپ</a>
      </section>

      @if ($featured->isNotEmpty())
        <section class="wd wd-feat" aria-labelledby="w-feat">
          <header class="wd-h"><h2 id="w-feat">منتخب‌ها</h2><span class="tn">{{ Fa::digits($featured->count()) }} محصول</span></header>
          <div class="hs feat">
            @foreach ($featured as $p)
              <x-pcard :p="$p" w="232" s="150" />
            @endforeach
          </div>
        </section>
      @endif

      <section class="wd wd-brands" aria-labelledby="w-brands">
        <header class="wd-h"><h2 id="w-brands">برندها</h2><span class="tn">{{ $brandCount }} برند، کنار هم و بی‌طرف</span></header>
        <nav class="bchips" aria-label="برندها">
          @foreach ($brands as $b)
            <a class="chip" href="{{ route('search', ['q' => $b->name]) }}"><span class="lat">{{ $b->name }}</span></a>
          @endforeach
        </nav>
      </section>

    </div>
  </div>
</x-layouts.site>
