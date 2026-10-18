{{-- Standalone page shell for sign-in and first-run setup (no sidebar). --}}
<!doctype html>
<html lang="fa" dir="rtl" class="fz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>{{ $title }} | پنل FRitZ</title>
<script>try { if (localStorage.getItem('fz-theme') === '"dark"') document.documentElement.classList.add('dark'); } catch (e) {}</script>
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/fz.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/adm.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/admin.css') }}">
<link rel="icon" type="image/png" href="{{ asset('img/fritz-logo.png') }}">
<script>window.FZ = {};</script>
<script defer src="{{ \App\Support\Asset::url('js/fz.js') }}"></script>
<script defer src="{{ \App\Support\Asset::url('js/alpine.min.js') }}"></script>
</head>
<body x-data>
@include('partials.art-sprite')
<div class="auth">
  <main>@yield('form')</main>
  <div class="ph">
    <x-art kind="panel7" s="360" label="" aria-hidden="true" style="justify-self:center" />
    <div style="text-align:center;max-width:380px;justify-self:center">@yield('aside')</div>
  </div>
</div>
</body>
</html>
