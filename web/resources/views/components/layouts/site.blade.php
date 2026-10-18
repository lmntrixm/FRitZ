@props(['title' => null, 'description' => null, 'stick' => false])
@php
  use App\Models\Setting;
  use App\Support\Asset;
  $seoTitle = Setting::get('seo_title', 'FRitZ');
  $pageTitle = trim(($title ?? '') !== '' ? $title . ' | FRitZ' : $seoTitle);
  $fz = [
    'csrf' => csrf_token(),
    'wa' => preg_replace('/\D+/', '', (string) Setting::get('whatsapp_number', '')),
    'template' => Setting::get('message_template'),
    'inquiryUrl' => route('inquiry.log'),
    'compareUrl' => route('compare'),
  ];
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
<meta name="theme-color" content="#FFFFFF">
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
<body x-data @class(["has-stick" => $stick ?? false])>
<a class="skip" href="#main">رفتن به محتوای اصلی</a>
@include('partials.art-sprite')
<div class="site">
  {{ $header ?? '' }}
  <main id="main" tabindex="-1">
    {{ $slot }}
  </main>
  {{ $footer ?? '' }}
</div>
{{ $overlays ?? '' }}
@include('partials.inquiry-drawer')
@include('partials.toast')
</body>
</html>
