{{-- Admin shell: ink icon rail, catalog tree panel, top bar with Ctrl+K and «+ جدید», white work surface.
     Under 900px rail and tree become one drawer. Direction: .impeccable/surfaces (admin layout brief). --}}
@props(['title', 'active' => null, 'actions' => null, 'sub' => null, 'crumbs' => null, 'flush' => false])
@php
  use App\Support\Asset;
  use App\Support\Fa;
  $u = auth()->user();
  $admin = $u->isAdmin();
  $newInq = \App\Models\Inquiry::where('status', 'new')->count();
  $tree = \App\Support\AdminTree::get();
  $rail = [
    ['k' => 'overview', 'l' => 'خانه', 'i' => 'home', 'url' => route('admin.overview')],
    ['k' => 'products', 'l' => 'محصولات', 'i' => 'box', 'url' => route('admin.products.index')],
    ['k' => 'inquiries', 'l' => 'استعلام', 'i' => 'chat', 'n' => $newInq],
    ['k' => 'media', 'l' => 'رسانه', 'i' => 'image'],
    ['k' => 'categories', 'l' => 'دسته‌ها', 'i' => 'folder'],
    ['k' => 'brands', 'l' => 'برندها', 'i' => 'tag'],
    ['g' => true],
    ['k' => 'update', 'l' => 'به‌روزرسانی', 'i' => 'upload', 'lock' => true, 'url' => route('admin.update.index')],
    ['k' => 'users', 'l' => 'کاربران', 'i' => 'users', 'lock' => true],
    ['k' => 'activity', 'l' => 'فعالیت', 'i' => 'activity'],
    ['k' => 'settings', 'l' => 'تنظیمات', 'i' => 'settings', 'lock' => true, 'url' => route('admin.settings')],
  ];
  $rail = array_values(array_filter($rail, fn ($n) => ! ($n['lock'] ?? false) || $admin));
  $initials = collect(preg_split('/\s+/u', trim($u->name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join('');
  $roleLabel = $admin ? 'مدیر کل' : 'ویرایشگر محتوا';

  // which tree row is the current one (products list only)
  $onList = request()->routeIs('admin.products.index');
  $q = request()->query();
  $cur = ! $onList ? null : (isset($q['category']) ? 'c' . (int) $q['category']
    : (($q['status'] ?? '') === 'trashed' ? 'trashed' : (($q['status'] ?? '') === 'draft' ? 'draft' : (! empty($q['incomplete']) ? 'open' : (count(array_diff_key($q, ['view' => 1, 'page' => 1, 'sort' => 1])) ? null : 'all')))));

  // command palette: every place and action this person may use
  $cmds = collect([
    ['l' => 'افزودن محصول', 'i' => 'plus', 'u' => route('admin.products.create'), 'g' => 'کار'],
    $admin ? ['l' => 'به‌روزرسانی از فایل اکسل', 'i' => 'table', 'u' => route('admin.update.index', ['tab' => 'xlsx']), 'g' => 'کار'] : null,
    $admin ? ['l' => 'بارگذاری بسته .fritz', 'i' => 'lock', 'u' => route('admin.update.index'), 'g' => 'کار'] : null,
    $admin ? ['l' => 'خروجی اکسل همه محصولات', 'i' => 'download', 'u' => route('admin.update.export'), 'g' => 'کار'] : null,
    ['l' => 'محصولات ناقص', 'i' => 'alert', 'u' => route('admin.products.index', ['incomplete' => 1]), 'g' => 'کار'],
    ['l' => 'پیش‌نویس‌ها', 'i' => 'edit', 'u' => route('admin.products.index', ['status' => 'draft']), 'g' => 'کار'],
  ])->filter()->concat(collect($rail)->filter(fn ($n) => isset($n['k']))->map(fn ($n) => ['l' => $n['k'] === 'overview' ? 'نمای کلی' : $n['l'], 'i' => $n['i'], 'u' => $n['url'] ?? route('admin.soon', $n['k']), 'g' => 'رفتن به']))
    ->concat(collect($tree['groups'])->flatMap(fn ($g) => $g['items'])->map(fn ($c) => ['l' => $c['name'], 'i' => $c['icon'], 'u' => route('admin.products.index', ['category' => $c['id']]), 'g' => 'دسته‌ها']))
    ->values();
@endphp
<!doctype html>
<html lang="fa" dir="rtl" class="fz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>{{ $title }} | پنل FRitZ</title>
<script>try { if (localStorage.getItem('fz-theme') === '"dark"') document.documentElement.classList.add('dark'); if (localStorage.getItem('fz-tree') === '0') document.documentElement.classList.add('no-tree'); } catch (e) {}</script>
<link rel="preload" href="{{ asset('fonts/kalameh-regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ Asset::url('css/fz.css') }}">
<link rel="stylesheet" href="{{ Asset::url('css/adm.css') }}">
<link rel="stylesheet" href="{{ Asset::url('css/admin.css') }}">
<link rel="icon" type="image/png" href="{{ asset('img/fritz-logo.png') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>window.FZ = { lookup: @js(route('admin.products.lookup')) };</script>
<script defer src="{{ Asset::url('js/fz.js') }}"></script>
<script defer src="{{ Asset::url('js/admin.js') }}"></script>
<script defer src="{{ Asset::url('js/alpine-focus.min.js') }}"></script>
<script defer src="{{ Asset::url('js/alpine.min.js') }}"></script>
@stack('head')
</head>
<body x-data="{ menu: false, me: false }" @keydown.escape.window="menu = false; me = false">
<a class="skip" href="#main">رفتن به محتوای اصلی</a>
@include('partials.art-sprite')
<div class="app">
  <div class="nav" :class="menu && 'open'" x-trap.inert.noscroll="menu">
    {{-- rail: the sections --}}
    <nav class="rail" aria-label="بخش‌ها">
      <a class="mark" href="{{ route('admin.overview') }}"><img src="{{ asset('img/fritz-logo-dark.png') }}" alt="FRitZ، نمای کلی"></a>
      @foreach ($rail as $n)
        @if (isset($n['g']))
          <span class="rsep" aria-hidden="true"></span>
        @else
          <a @class(['ri', 'on' => $active === $n['k']]) href="{{ $n['url'] ?? route('admin.soon', $n['k']) }}" @if($active === $n['k']) aria-current="page" @endif>
            <span class="rico"><x-icon :i="$n['i']" s="20" />@if (! empty($n['n']))<span class="rb">{{ Fa::digits($n['n']) }}</span>@endif</span>
            <span class="rl">{{ $n['l'] }}</span>
          </a>
        @endif
      @endforeach
      <span style="flex:1"></span>
      <button class="ri" type="button" @click="$store.theme.toggle()" :aria-label="$store.theme.dark ? 'تم روشن' : 'تم تیره'">
        <span class="rico"><span x-show="!$store.theme.dark"><x-icon i="moon" s="20" /></span><span x-show="$store.theme.dark" x-cloak><x-icon i="sun" s="20" /></span></span>
      </button>
    </nav>

    {{-- tree: the catalog, always one click away --}}
    <aside class="tree" aria-label="کاتالوگ">
      <div class="tree-h">
        <b class="tws">کاتالوگ FRitZ</b>
        <button class="ib sm mclose" type="button" aria-label="بستن منو" @click="menu = false"><x-icon i="x" s="18" /></button>
        <button class="ib sm tclose" type="button" aria-label="بستن پنل کاتالوگ" title="بستن پنل" onclick="fzTree(false)"><x-icon i="chevNext" s="18" /></button>
      </div>
      <button class="tsearch" type="button" @click="$dispatch('palette')"><x-icon i="search" s="16" /><span>جست‌وجو</span><kbd class="kbd">Ctrl K</kbd></button>

      <div class="tsec">
        <span class="tg">نماها</span>
        @foreach ([['all', 'همه محصولات', 'grid2', $tree['all'], []], ['open', 'ناقص‌ها', 'alert', $tree['open'], ['incomplete' => 1]], ['draft', 'پیش‌نویس‌ها', 'edit', $tree['draft'], ['status' => 'draft']], ['trashed', 'حذف‌شده‌ها', 'trash', $tree['trashed'], ['status' => 'trashed']]] as [$k, $l, $i, $n, $qq])
          <a @class(['ti', 'on' => $cur === $k]) href="{{ route('admin.products.index', $qq) }}" @if($cur === $k) aria-current="page" @endif>
            <x-icon :i="$i" s="16" /><span class="tl">{{ $l }}</span><span class="tn">{{ Fa::digits($n) }}</span></a>
        @endforeach
      </div>

      @foreach ($tree['groups'] as $gi => $g)
        <div class="tsec" x-data="{ o: true }">
          <button class="tg tgb" type="button" @click="o = !o" :aria-expanded="o"><x-icon i="chevDown" s="14" />{{ $g['name'] }}</button>
          <div x-show="o">
            @foreach ($g['items'] as $c)
              <a @class(['ti', 'on' => $cur === 'c' . $c['id'], 'none' => ! $c['n']]) href="{{ route('admin.products.index', ['category' => $c['id']]) }}" @if($cur === 'c' . $c['id']) aria-current="page" @endif
                 title="{{ $c['n'] ? Fa::digits($c['n']) . ' محصول' . ($c['open'] ? '، ' . Fa::digits($c['open']) . ' ناقص' : '، همه کامل') : 'هنوز محصولی ندارد' }}">
                <span class="tic"><x-icon :i="$c['icon']" s="16" /></span><span class="tl">{{ $c['name'] }}</span>
                @if ($c['n'])<span class="tm" aria-hidden="true"><i style="width:{{ round(($c['n'] - $c['open']) / $c['n'] * 100) }}%"></i></span>@endif
                <span class="tn">{{ Fa::digits($c['n']) }}</span></a>
            @endforeach
          </div>
        </div>
      @endforeach

      <div class="me">
        <span class="av">{{ $initials }}</span>
        <span style="display:flex;flex-direction:column;line-height:1.5;min-width:0;flex:1"><b style="font-size:13px">{{ $u->name }}</b><span class="t-c mut3">{{ $roleLabel }}</span></span>
        <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="ib sm" type="submit" aria-label="خروج" title="خروج"><x-icon i="logout" s="18" /></button></form>
      </div>
    </aside>
  </div>
  <div class="scrim" x-show="menu" x-cloak @click="menu = false"></div>

  <div class="main">
    <header class="top">
      <button class="ib sm mopen" type="button" aria-label="منو" @click="menu = true" :aria-expanded="menu"><x-icon i="menu" s="20" /></button>
      <button class="ib sm topen" type="button" aria-label="باز کردن پنل کاتالوگ" title="پنل کاتالوگ" onclick="fzTree(true)"><x-icon i="chevBack" s="18" /></button>
      <nav class="crumb" aria-label="مسیر">
        @foreach ($crumbs ?? [] as [$cl, $cu])<a href="{{ $cu }}">{{ $cl }}</a><x-icon i="chevNext" s="12" />@endforeach
        <span aria-current="page">{{ $title }}</span>
      </nav>
      <button class="gsearch" type="button" @click="$dispatch('palette')" aria-keyshortcuts="Control+K"><x-icon i="search" s="16" /><span>جست‌وجو یا فرمان…</span><kbd class="kbd">Ctrl K</kbd></button>
      <div class="topr">
        <div class="newm" x-data="{ o: false }" @click.outside="o = false" @keydown.escape="o = false">
          <button class="btn btn-ink btn-sm" type="button" @click="o = !o" :aria-expanded="o" aria-haspopup="menu"><x-icon i="plus" s="16" sw="2.2" /><span>جدید</span></button>
          <div class="menu" role="menu" x-show="o" x-cloak x-transition.opacity.duration.150ms>
            <a role="menuitem" href="{{ route('admin.products.create') }}"><x-icon i="box" s="16" />محصول</a>
            @if ($admin)
              <a role="menuitem" href="{{ route('admin.update.index', ['tab' => 'xlsx']) }}"><x-icon i="table" s="16" />به‌روزرسانی از اکسل</a>
              <a role="menuitem" href="{{ route('admin.update.index') }}"><x-icon i="lock" s="16" />بسته <span class="lat">.fritz</span></a>
            @endif
          </div>
        </div>
        <a class="ib sm" href="{{ route('home') }}" target="_blank" aria-label="مشاهده کاتالوگ عمومی" title="مشاهده کاتالوگ عمومی"><x-icon i="external" s="18" /></a>
        <div style="position:relative" @click.outside="me = false">
          <button class="avb" type="button" @click="me = !me" :aria-expanded="me" aria-haspopup="menu" aria-label="حساب {{ $u->name }}"><span class="av sm">{{ $initials }}</span></button>
          <div class="menu" role="menu" x-show="me" x-cloak x-transition.opacity.duration.150ms style="min-width:220px">
            <span class="mh"><b>{{ $u->name }}</b><span class="t-c mut3">{{ $roleLabel }} · <bdi class="lat">{{ $u->email }}</bdi></span></span>
            <button role="menuitem" type="button" @click="$store.theme.toggle()"><x-icon i="moon" s="16" /><span x-text="$store.theme.dark ? 'تم روشن' : 'تم تیره'">تم تیره</span></button>
            <form method="post" action="{{ route('admin.logout') }}">@csrf<button role="menuitem" type="submit"><x-icon i="logout" s="16" />خروج</button></form>
          </div>
        </div>
      </div>
    </header>

    <div @class(['content', 'flush' => $flush]) id="main" tabindex="-1">
      <div class="phead">
        <div style="min-width:0">
          <h1 class="ptitle">{{ $title }}</h1>
          @if ($sub)<p class="t-s mut">{{ $sub }}</p>@endif
        </div>
        @if ($actions)<div class="acts">{{ $actions }}</div>@endif
      </div>
      @if (session('ok'))
        <div class="msgbox ok flash" role="status"><x-icon i="checkCircle" s="18" /><span>{{ session('ok') }}</span></div>
      @endif
      {{ $slot }}
    </div>
  </div>
</div>

{{-- Ctrl+K: places, actions, categories and products in one list --}}
<div class="pal-wrap" x-data="palette(@js($cmds))" @palette.window="show()" @keydown.window.ctrl.k.prevent="show()" @keydown.window.meta.k.prevent="show()" x-show="open" x-cloak>
  <div class="scrim" @click="open = false"></div>
  <div class="pal" role="dialog" aria-modal="true" aria-label="جست‌وجو و فرمان" x-trap.noscroll="open" @keydown.escape.stop="open = false">
    <div class="pal-in"><x-icon i="search" s="18" />
      <input x-ref="q" x-model="q" @input.debounce.180ms="find()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="go()"
             role="combobox" aria-expanded="true" aria-controls="pal-list" :aria-activedescendant="'pal-' + i" placeholder="نام یا کد محصول، بخش یا کار" autocomplete="off" spellcheck="false">
      <kbd class="kbd">Esc</kbd></div>
    <ul class="pal-list" id="pal-list" role="listbox">
      <template x-for="(r, n) in list" :key="n">
        <li>
          <span class="pal-g" x-show="n === 0 || list[n - 1].g !== r.g" x-text="r.g"></span>
          <a :id="'pal-' + n" role="option" :aria-selected="n === i" :class="n === i && 'on'" :href="r.u" @mouseenter="i = n">
            <span class="pal-ic" x-html="r.svg || icon(r.i)"></span><span class="pal-l" x-text="r.l"></span><span class="code" x-show="r.c" x-text="r.c"></span></a>
        </li>
      </template>
      <li x-show="!list.length" class="pal-none" x-text="busy ? 'در حال جست‌وجو…' : 'چیزی پیدا نشد. نام فارسی یا کد مدل را امتحان کنید.'"></li>
    </ul>
    <div class="pal-f"><span><kbd class="kbd">↑↓</kbd> جابه‌جایی</span><span><kbd class="kbd">Enter</kbd> باز کردن</span></div>
  </div>
</div>
<template id="pal-icons">@foreach (collect($cmds)->pluck('i')->push('box')->unique() as $ic)<span data-i="{{ $ic }}"><x-icon :i="$ic" s="16" /></span>@endforeach</template>

{{-- confirmation for dangerous actions: says what will happen, the cancel button is the safe default --}}
<div x-show="$store.confirm.open" x-cloak @keydown.escape.window="$store.confirm.open && $store.confirm.cancel()" style="position:fixed;inset:0;z-index:30;display:grid;place-items:center">
  <div class="scrim" @click="$store.confirm.cancel()"></div>
  <div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="cf-t" aria-describedby="cf-b" x-trap.noscroll="$store.confirm.open">
    <span style="width:48px;height:48px;border-radius:50%;display:grid;place-items:center" :style="$store.confirm.danger ? 'background:var(--errS);color:var(--err)' : 'background:var(--wnS);color:var(--wn)'"><x-icon i="alert" s="24" /></span>
    <h2 class="t-h2" id="cf-t" x-text="$store.confirm.title"></h2>
    <p class="t-s mut" id="cf-b" x-text="$store.confirm.body"></p>
    <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">
      <button type="button" class="btn btn-s btn-sm" @click="$store.confirm.cancel()">انصراف</button>
      <button type="button" class="btn btn-sm" :class="$store.confirm.danger ? 'btn-x' : 'btn-p'" @click="$store.confirm.yes()" x-text="$store.confirm.ok"></button>
    </div>
  </div>
</div>
<div class="toast" role="status" aria-live="polite" x-show="$store.toast.msg" x-cloak x-text="$store.toast.msg"></div>
</body>
</html>
