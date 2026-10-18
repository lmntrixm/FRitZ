{{-- Catalog header. Home: logo. Inner pages: back, title and subtitle, search. Both: theme and inquiry list. --}}
@props(['title' => null, 'sub' => null, 'back' => null, 'search' => true])
<header {{ $attributes->merge(['class' => 'hdr']) }}>
  @if ($title === null)
    <a class="logo" href="{{ route('home') }}" aria-label="FRitZ، صفحه اصلی"><span class="mk"></span><b>FRitZ</b></a>
  @else
    <a class="ib" href="{{ $back ?? route('home') }}" aria-label="بازگشت"
       @click.prevent="document.referrer.startsWith(location.origin) && history.length > 1 ? history.back() : (location.href = $el.href)"><x-icon i="chevBack" /></a>
    <div class="ttl">
      <b>{{ $title }}</b>
      @if ($sub)<span class="t-c mut3">{{ $sub }}</span>@endif
    </div>
  @endif
  <div class="sp"></div>
  @if ($title !== null && $search)
    <a class="ib" href="{{ route('search') }}" aria-label="جست‌وجو"><x-icon i="search" /></a>
  @endif
  {{ $slot }}
  <button class="ib" type="button" @click="$store.theme.toggle()" :aria-label="$store.theme.dark ? 'تم روشن' : 'تم تیره'" aria-label="تم تیره">
    <span x-show="!$store.theme.dark"><x-icon i="moon" /></span>
    <span x-show="$store.theme.dark" x-cloak><x-icon i="sun" /></span>
  </button>
  <button class="ib" type="button" @click="$store.inq.show()" :aria-label="'لیست استعلام، ' + FA($store.inq.count) + ' قلم'" aria-label="لیست استعلام">
    <x-icon i="inquiry" />
    <span class="cnt" x-show="$store.inq.count" x-text="FA($store.inq.count)" x-cloak></span>
  </button>
</header>
