{{-- Catalog top bar. Phones: menu (or back), title, theme. Desktop: breadcrumb, Ctrl+K search, inquiry list with its count. --}}
@props(['title' => null, 'sub' => null, 'back' => null, 'search' => true, 'crumbs' => []])
<header {{ $attributes->merge(['class' => 'ctop']) }}>
  @if ($back)
    <a class="ib sm mob" href="{{ $back }}" aria-label="بازگشت"
       @click.prevent="document.referrer.startsWith(location.origin) && history.length > 1 ? history.back() : (location.href = $el.href)"><x-icon i="chevBack" s="20" /></a>
  @else
    <button class="ib sm mob" type="button" aria-label="منو و دسته‌ها" @click="$dispatch('nav')"><x-icon i="menu" s="20" /></button>
  @endif
  @if ($title === null)
    <a class="logo mob" href="{{ route('home') }}" aria-label="FRitZ، صفحه اصلی"><span class="mk"></span><b>FRitZ</b></a>
  @endif
  <nav class="crumb" aria-label="مسیر">
    @if ($title !== null)
      <a class="dsk" href="{{ route('home') }}">کاتالوگ</a><span class="dsk" aria-hidden="true"><x-icon i="chevNext" s="12" /></span>
      @foreach ($crumbs as [$cl, $cu])<a class="dsk" href="{{ $cu }}">{{ $cl }}</a><span class="dsk" aria-hidden="true"><x-icon i="chevNext" s="12" /></span>@endforeach
      <span class="ttl"><b aria-current="page">{{ $title }}</b>@if ($sub)<span class="t-c mut3">{{ $sub }}</span>@endif</span>
    @else
      <span class="dsk" style="font-weight:700;color:var(--tx)">کاتالوگ تجهیزات هوشمند</span>
    @endif
  </nav>
  @if ($search)
    <button class="gsearch dsk" type="button" @click="$dispatch('palette')" aria-keyshortcuts="Control+K"><x-icon i="search" s="16" /><span>جست‌وجوی محصول یا کد مدل</span><kbd class="kbd">Ctrl K</kbd></button>
  @endif
  <div class="topr">
    {{ $slot }}
    <button class="ib sm" type="button" @click="$store.theme.toggle()" :aria-label="$store.theme.dark ? 'تم روشن' : 'تم تیره'" aria-label="تم تیره">
      <span x-show="!$store.theme.dark"><x-icon i="moon" s="18" /></span>
      <span x-show="$store.theme.dark" x-cloak><x-icon i="sun" s="18" /></span>
    </button>
    <button class="ib sm inqm" type="button" @click="$store.inq.show()" :aria-label="'لیست استعلام، ' + FA($store.inq.count) + ' قلم'" aria-label="لیست استعلام"><x-icon i="inquiry" s="20" /><span class="cnt" x-show="$store.inq.count" x-cloak x-text="FA($store.inq.count)"></span></button>
    <button class="inqb dsk" type="button" :class="$store.inq.count && 'has'" @click="$store.inq.show()" :aria-label="'لیست استعلام، ' + FA($store.inq.count) + ' قلم'" aria-label="لیست استعلام">
      <x-icon i="inquiry" s="18" /><span>لیست استعلام</span><span class="n" x-text="FA($store.inq.count)">۰</span>
    </button>
  </div>
</header>
