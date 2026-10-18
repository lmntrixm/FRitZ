{{-- Public catalog shell: category tree beside the work surface, Ctrl+K search, bottom tab bar on phones.
     Direction: .impeccable/surfaces (site layout brief). No price, stock or cart anywhere here. --}}
@props(['title' => null, 'description' => null, 'stick' => false, 'wide' => false, 'back' => null, 'sub' => null, 'bare' => false])
@php
  use App\Models\Setting;
  use App\Support\Asset;
  use App\Support\Fa;
  $seoTitle = Setting::get('seo_title', 'FRitZ');
  $pageTitle = trim(($title ?? '') !== '' ? $title . ' | FRitZ' : $seoTitle);
  $wa = preg_replace('/\D+/', '', (string) Setting::get('whatsapp_number', ''));
  $fz = [
    'csrf' => csrf_token(),
    'wa' => $wa,
    'template' => Setting::get('message_template'),
    'inquiryUrl' => route('inquiry.log'),
    'compareUrl' => route('compare'),
    'suggest' => route('search.suggest'),
    'search' => route('search'),
  ];
  $nav = \App\Support\CatalogNav::get();
  $curCat = request()->route('category')?->slug;
  $cmds = collect([
    ['l' => 'خانه', 'i' => 'home', 'u' => route('home'), 'g' => 'صفحه‌ها'],
    ['l' => 'راهنمای انتخاب محصول', 'i' => 'compass', 'u' => route('finder'), 'g' => 'صفحه‌ها'],
    ['l' => 'مقایسه', 'i' => 'compare', 'u' => route('compare'), 'g' => 'صفحه‌ها'],
    ['l' => 'درباره و تماس', 'i' => 'info', 'u' => route('about'), 'g' => 'صفحه‌ها'],
  ])->concat(collect($nav['groups'])->flatMap(fn ($g) => $g['items'])->map(fn ($c) => ['l' => $c['name'], 'i' => $c['icon'], 'u' => route('category', $c['slug']), 'g' => 'دسته‌ها']))
    ->concat(collect($nav['brands'])->map(fn ($b) => ['l' => $b['name'], 'i' => 'tag', 'u' => route('search', ['q' => $b['name']]), 'g' => 'برندها']))
    ->values();
@endphp
<!doctype html>
<html lang="fa" dir="rtl" class="fz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $description ?? Setting::get('seo_description') }}">
@unless (Setting::get('allow_indexing', false))<meta name="robots" content="noindex, nofollow">@endunless
<link rel="canonical" href="{{ url()->current() }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#F3F3F1">
<script>
  /* theme before first paint: light is the default */
  try { if (localStorage.getItem('fz-theme') === '"dark"') document.documentElement.classList.add('dark'); } catch (e) {}
</script>
<link rel="preload" href="{{ asset('fonts/kalameh-regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/kalameh-extrabold.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ Asset::url('css/fz.css') }}">
<link rel="stylesheet" href="{{ Asset::url('css/site.css') }}">
<link rel="icon" type="image/png" href="{{ asset('img/fritz-logo.png') }}">
<script>
  window.FZ = {!! json_encode($fz, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script defer src="{{ Asset::url('js/fz.js') }}"></script>
<script defer src="{{ Asset::url('js/alpine-focus.min.js') }}"></script>
<script defer src="{{ Asset::url('js/alpine.min.js') }}"></script>
@stack('head')
</head>
<body x-data="{ nav: false }" @nav.window="nav = true" @keydown.escape.window="nav = false" @class(['has-stick' => $stick])>
<a class="skip" href="#main">رفتن به محتوای اصلی</a>
@include('partials.art-sprite')
<div class="capp">
  {{-- sidebar: the catalog as a tree --}}
  <aside class="cside" :class="nav && 'open'" aria-label="کاتالوگ" x-trap.inert.noscroll="nav">
    <div class="cside-h">
      <a class="logo" href="{{ route('home') }}" aria-label="FRitZ، صفحه اصلی"><span class="mk"></span><b>FRitZ</b></a>
      <button class="ib sm cclose" type="button" aria-label="بستن منو" @click="nav = false"><x-icon i="x" s="20" /></button>
    </div>
    <button class="tsearch" type="button" @click="nav = false; $dispatch('palette')"><x-icon i="search" s="16" /><span>محصول یا کد مدل</span><kbd class="kbd">Ctrl K</kbd></button>
    <nav class="tsec" aria-label="صفحه‌ها">
      @foreach ([['home', 'خانه', 'home', route('home')], ['finder', 'راهنمای انتخاب', 'compass', route('finder')], ['compare', 'مقایسه', 'compare', route('compare')], ['about', 'درباره و تماس', 'info', route('about')]] as [$k, $l, $ic, $u])
        @php $on = request()->routeIs($k === 'finder' ? 'finder*' : $k); @endphp
        <a @class(['ti', 'on' => $on]) href="{{ $u }}" @if($on) aria-current="page" @endif><x-icon :i="$ic" s="16" /><span class="tl">{{ $l }}</span>
          @if ($k === 'compare')<span class="tn" x-show="$store.cmp.items.length" x-cloak x-text="FA($store.cmp.items.length)"></span>@endif</a>
      @endforeach
    </nav>
    @foreach ($nav['groups'] as $g)
      <div class="tsec" x-data="{ o: true }">
        <button class="tg tgb" type="button" @click="o = !o" :aria-expanded="o"><x-icon i="chevDown" s="14" />{{ $g['name'] }}</button>
        <div x-show="o">
          @foreach ($g['items'] as $c)
            <a @class(['ti', 'on' => $curCat === $c['slug'], 'none' => ! $c['n']]) href="{{ route('category', $c['slug']) }}" @if($curCat === $c['slug']) aria-current="page" @endif>
              <span class="tic"><x-icon :i="$c['icon']" s="16" /></span><span class="tl">{{ $c['name'] }}</span><span class="tn">{{ Fa::digits($c['n']) }}</span></a>
          @endforeach
        </div>
      </div>
    @endforeach
    @if ($nav['brands'])
      <div class="tsec" x-data="{ o: false }">
        <button class="tg tgb" type="button" @click="o = !o" :aria-expanded="o"><x-icon i="chevDown" s="14" />برندها</button>
        <div x-show="o" x-cloak>
          @foreach ($nav['brands'] as $b)
            <a class="ti" href="{{ route('search', ['q' => $b['name']]) }}"><x-icon i="tag" s="16" /><span class="tl lat">{{ $b['name'] }}</span><span class="tn">{{ Fa::digits($b['n']) }}</span></a>
          @endforeach
        </div>
      </div>
    @endif
    <div class="cside-f">
      <a class="btn btn-s btn-sm btn-w" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><x-icon i="chat" s="18" />گفت‌وگو با کارشناس</a>
      <button class="ib sm" type="button" @click="$store.theme.toggle()" :aria-label="$store.theme.dark ? 'تم روشن' : 'تم تیره'" aria-label="تم تیره">
        <span x-show="!$store.theme.dark"><x-icon i="moon" s="18" /></span><span x-show="$store.theme.dark" x-cloak><x-icon i="sun" s="18" /></span></button>
    </div>
  </aside>
  <div class="scrim cscrim" x-show="nav" x-cloak @click="nav = false"></div>

  <div class="cmain">
    @if (isset($header))
      {{ $header }}
    @elseif (! $bare)
      <x-hdr :title="$title" :sub="$sub" :back="$back" />
    @endif
    <main id="main" tabindex="-1" @class(['cpage', 'narrow' => ! $wide])>
      {{ $slot }}
    </main>
    {{ $footer ?? '' }}
  </div>
</div>

{{-- phones: the five places people go --}}
<nav class="tabbar" aria-label="ناوبری اصلی">
  <a href="{{ route('home') }}" @class(['on' => request()->routeIs('home')]) @if(request()->routeIs('home')) aria-current="page" @endif><x-icon i="home" s="22" /><span>خانه</span></a>
  <button type="button" @click="nav = true" @class(['on' => request()->routeIs('category')])><x-icon i="grid2" s="22" /><span>دسته‌ها</span></button>
  <button type="button" @click="$dispatch('palette')"><x-icon i="search" s="22" /><span>جست‌وجو</span></button>
  <a href="{{ route('compare') }}" @class(['on' => request()->routeIs('compare')])><span class="tbi"><x-icon i="compare" s="22" /><span class="cnt" x-show="$store.cmp.items.length" x-cloak x-text="FA($store.cmp.items.length)"></span></span><span>مقایسه</span></a>
  <button type="button" @click="$store.inq.show()" :aria-label="'لیست استعلام، ' + FA($store.inq.count) + ' قلم'"><span class="tbi"><x-icon i="inquiry" s="22" /><span class="cnt" x-show="$store.inq.count" x-cloak x-text="FA($store.inq.count)"></span></span><span>استعلام</span></button>
</nav>

{{-- Ctrl+K: products by name or code, categories, brands, pages --}}
<div class="pal-wrap" x-data="cpalette(@js($cmds))" @palette.window="show()" @keydown.window.ctrl.k.prevent="show()" @keydown.window.meta.k.prevent="show()" x-show="open" x-cloak>
  <div class="scrim" @click="open = false"></div>
  <div class="pal" role="dialog" aria-modal="true" aria-label="جست‌وجو" x-trap.noscroll="open" @keydown.escape.stop="open = false">
    <div class="pal-in"><x-icon i="search" s="18" />
      <input x-ref="q" x-model="q" @input.debounce.180ms="find()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="go()"
             role="combobox" aria-expanded="true" aria-controls="cpal-list" :aria-activedescendant="list.length ? 'cpal-' + i : null" placeholder="نام محصول، کد مدل، دسته یا برند" autocomplete="off" spellcheck="false" enterkeyhint="search">
      <button class="kbd" type="button" @click="open = false">Esc</button></div>
    <ul class="pal-list" id="cpal-list" role="listbox">
      <template x-for="(r, n) in list" :key="n">
        <li>
          <span class="pal-g" x-show="n === 0 || list[n - 1].g !== r.g" x-text="r.g"></span>
          <a :id="'cpal-' + n" role="option" :aria-selected="n === i" :class="n === i && 'on'" :href="r.u" @mouseenter="i = n">
            <span class="pal-ic">
              <template x-if="r.img"><img :src="r.img" alt=""></template>
              <template x-if="!r.img && r.art"><svg class="art" width="28" height="28" viewBox="0 0 200 200" aria-hidden="true"><use :href="'#art-' + r.art"/></svg></template>
              <template x-if="!r.img && !r.art"><span x-html="icon(r.i)"></span></template>
            </span>
            <span class="pal-l"><span x-text="r.l"></span><span class="pal-s" x-show="r.s" x-text="r.s"></span></span><span class="code" x-show="r.c" x-text="r.c"></span></a>
        </li>
      </template>
      <li x-show="!list.length" class="pal-none" x-text="busy ? 'در حال جست‌وجو…' : 'چیزی پیدا نشد. نام فارسی، کد مدل یا برند را امتحان کنید.'"></li>
    </ul>
    <div class="pal-f"><span><kbd class="kbd">↑↓</kbd> جابه‌جایی</span><span><kbd class="kbd">Enter</kbd> باز کردن</span></div>
  </div>
</div>
<template id="pal-icons">@foreach ($cmds->pluck('i')->push('search')->unique() as $ic)<span data-i="{{ $ic }}"><x-icon :i="$ic" s="16" /></span>@endforeach</template>

{{ $overlays ?? '' }}
@include('partials.inquiry-drawer')
@include('partials.toast')
</body>
</html>
