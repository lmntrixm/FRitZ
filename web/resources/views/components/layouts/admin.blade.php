{{-- Admin shell (A-* artboards): sidebar on wide screens, drawer under 900px (A-Mobile). --}}
@props(['title', 'active' => null, 'actions' => null, 'sub' => null])
@php
  use App\Support\Asset;
  use App\Support\Fa;
  $u = auth()->user();
  $newInq = \App\Models\Inquiry::where('status', 'new')->count();
  $nav = [
    ['k' => 'overview', 'l' => 'نمای کلی', 'i' => 'grid2', 'url' => route('admin.overview')],
    ['k' => 'products', 'l' => 'محصولات', 'i' => 'box', 'url' => route('admin.products.index')],
    ['k' => 'categories', 'l' => 'دسته‌ها و فیلترها', 'i' => 'folder'],
    ['k' => 'brands', 'l' => 'برندها و پروتکل‌ها', 'i' => 'tag'],
    ['k' => 'media', 'l' => 'رسانه', 'i' => 'image'],
    ['k' => 'inquiries', 'l' => 'استعلام‌ها', 'i' => 'chat', 'n' => $newInq],
    ['g' => 'مدیریت'],
    ['k' => 'update', 'l' => 'به‌روزرسانی', 'i' => 'upload', 'lock' => true, 'url' => route('admin.update.index')],
    ['k' => 'users', 'l' => 'کاربران', 'i' => 'users', 'lock' => true],
    ['k' => 'activity', 'l' => 'گزارش فعالیت', 'i' => 'activity'],
    ['k' => 'settings', 'l' => 'تنظیمات', 'i' => 'settings', 'lock' => true, 'url' => route('admin.settings')],
  ];
  $initials = collect(preg_split('/\s+/u', trim($u->name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join('');
  $roleLabel = $u->isAdmin() ? 'مدیر کل' : 'ویرایشگر محتوا';
@endphp
<!doctype html>
<html lang="fa" dir="rtl" class="fz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>{{ $title }} | پنل FRitZ</title>
<script>try { if (localStorage.getItem('fz-theme') === '"dark"') document.documentElement.classList.add('dark'); } catch (e) {}</script>
<link rel="preload" href="{{ asset('fonts/kalameh-regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ Asset::url('css/fz.css') }}">
<link rel="stylesheet" href="{{ Asset::url('css/adm.css') }}">
<link rel="stylesheet" href="{{ Asset::url('css/admin.css') }}">
<link rel="icon" type="image/png" href="{{ asset('img/fritz-logo.png') }}">
<script>window.FZ = {};</script>
<script defer src="{{ Asset::url('js/fz.js') }}"></script>
<script defer src="{{ Asset::url('js/admin.js') }}"></script>
<script defer src="{{ Asset::url('js/alpine-focus.min.js') }}"></script>
<script defer src="{{ Asset::url('js/alpine.min.js') }}"></script>
@stack('head')
</head>
<body x-data="{ menu: false }" @keydown.escape.window="menu = false">
<a class="skip" href="#main">رفتن به محتوای اصلی</a>
@include('partials.art-sprite')
<div class="adm">
  <aside class="side" :class="menu && 'open'" aria-label="منوی اصلی" x-trap.inert.noscroll="menu">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <a class="logo" href="{{ route('admin.overview') }}"><span class="mk"></span><span style="display:flex;flex-direction:column;gap:3px"><b>FRitZ</b><small>پنل مدیریت</small></span></a>
      <button class="ib mclose" type="button" aria-label="بستن منو" x-show="menu" x-cloak @click="menu = false"><x-icon i="x" /></button>
    </div>
    <nav style="display:flex;flex-direction:column;gap:2px">
      @foreach ($nav as $n)
        @if (isset($n['g']))
          @if ($u->isAdmin())<span class="ng">{{ $n['g'] }}</span>@endif
        @elseif (! ($n['lock'] ?? false) || $u->isAdmin())
          <a @class(['nv', 'on' => $active === $n['k']]) href="{{ $n['url'] ?? route('admin.soon', $n['k']) }}" @if($active === $n['k']) aria-current="page" @endif>
            <span class="nvi"><x-icon :i="$n['i']" s="18" /></span>{{ $n['l'] }}
            @if (! empty($n['n']))<span class="nb">{{ Fa::digits($n['n']) }}</span>@endif
            @if ($n['lock'] ?? false)<span class="nlock" title="فقط مدیر کل"><x-icon i="lock" s="14" /><span class="sr">فقط مدیر کل</span></span>@endif
          </a>
        @endif
      @endforeach
    </nav>
    <div class="me">
      <span class="av">{{ $initials }}</span>
      <span style="display:flex;flex-direction:column;line-height:1.5;min-width:0;flex:1"><b style="font-size:13px">{{ $u->name }}</b><span class="t-c mut3">{{ $roleLabel }}</span></span>
      <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="ib" type="submit" aria-label="خروج"><x-icon i="logout" s="18" /></button></form>
    </div>
  </aside>
  <div class="scrim" x-show="menu" x-cloak @click="menu = false"></div>

  <div class="main">
    <header class="hdr mhdr">
      <button class="ib" type="button" aria-label="منو" @click="menu = true" :aria-expanded="menu"><x-icon i="menu" /></button>
      <b style="font-size:16px">{{ $title }}</b>
      <div class="sp"></div>
      <a class="ib" href="{{ route('home') }}" target="_blank" aria-label="مشاهده کاتالوگ"><x-icon i="external" s="20" /></a>
      <button class="ib" type="button" @click="$store.theme.toggle()" :aria-label="$store.theme.dark ? 'تم روشن' : 'تم تیره'"><span x-show="!$store.theme.dark"><x-icon i="moon" s="20" /></span><span x-show="$store.theme.dark" x-cloak><x-icon i="sun" s="20" /></span></button>
    </header>
    <header class="top">
      <div style="flex:1"></div>
      <a class="btn btn-g btn-sm" href="{{ route('home') }}" target="_blank"><x-icon i="external" s="18" />مشاهده کاتالوگ</a>
      <button class="ib" type="button" @click="$store.theme.toggle()" :aria-label="$store.theme.dark ? 'تم روشن' : 'تم تیره'"><span x-show="!$store.theme.dark"><x-icon i="moon" s="20" /></span><span x-show="$store.theme.dark" x-cloak><x-icon i="sun" s="20" /></span></button>
      <span class="btn btn-g btn-sm" style="padding:0 8px;cursor:default"><span class="av sm">{{ $initials }}</span>{{ $u->name }}</span>
      <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="ib" type="submit" aria-label="خروج"><x-icon i="logout" s="20" /></button></form>
    </header>
    <div class="content" id="main" tabindex="-1">
      <div class="phead">
        <div>
          <nav class="crumb" aria-label="مسیر"><span>پنل</span><x-icon i="chevNext" s="12" /><span>{{ $title }}</span></nav>
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

{{-- confirmation for dangerous actions: says what will happen, the cancel button is the safe default --}}
<div x-show="$store.confirm.open" x-cloak @keydown.escape.window="$store.confirm.open = false" style="position:fixed;inset:0;z-index:9;display:grid;place-items:center">
  <div class="scrim" @click="$store.confirm.open = false"></div>
  <div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="cf-t" aria-describedby="cf-b" x-trap.noscroll="$store.confirm.open">
    <span style="width:48px;height:48px;border-radius:50%;display:grid;place-items:center" :style="$store.confirm.danger ? 'background:var(--errS);color:var(--err)' : 'background:var(--wnS);color:var(--wn)'"><x-icon i="alert" s="24" /></span>
    <h2 class="t-h2" id="cf-t" x-text="$store.confirm.title"></h2>
    <p class="t-s mut" id="cf-b" x-text="$store.confirm.body"></p>
    <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">
      <button type="button" class="btn btn-s btn-sm" @click="$store.confirm.open = false">انصراف</button>
      <button type="button" class="btn btn-sm" :class="$store.confirm.danger ? 'btn-x' : 'btn-p'" @click="$store.confirm.yes()" x-text="$store.confirm.ok"></button>
    </div>
  </div>
</div>
</body>
</html>
