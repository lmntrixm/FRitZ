{{-- Search (M-Search): recent + popular + finder link; live suggestions while typing; full results; no-result state. --}}
@php use App\Support\Fa; @endphp
<x-layouts.site :title="$q !== '' ? 'جست‌وجوی ' . $q : 'جست‌وجو'">
  <div x-data="searchBox(@js($q), @js(route('search.suggest')))">
    <form class="bar" role="search" action="{{ route('search') }}" method="get" style="padding:8px 8px 8px 12px;gap:4px;height:64px;position:sticky;top:0;z-index:3" @submit="remember(q)">
      <a class="ib" href="{{ route('home') }}" aria-label="بازگشت" @click.prevent="document.referrer.startsWith(location.origin) ? history.back() : (location.href = $el.href)"><x-icon i="chevBack" /></a>
      <div class="field" style="flex:1">
        <x-icon i="search" s="20" />
        <input name="q" x-ref="q" x-model="q" @input.debounce.200ms="suggest()" placeholder="نام محصول یا کد مدل" aria-label="جست‌وجو" autocomplete="off" enterkeyhint="search"
               :dir="/^[\x00-\x7F]+$/.test(q) && q ? 'ltr' : 'rtl'" style="text-align:right" @if($q === '') autofocus @endif>
        <button class="ib" type="button" style="width:36px;height:36px" aria-label="پاک کردن متن" x-show="q" x-cloak @click="q = ''; s = null; $refs.q.focus()"><x-icon i="x" s="18" /></button>
      </div>
    </form>

    {{-- idle: recent, popular, finder --}}
    <div x-show="!q.trim()" @if($q !== '') x-cloak @endif style="padding:8px 0">
      <template x-if="recent.length">
        <div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px 4px"><h2 class="t-h3">جست‌وجوهای اخیر</h2><button class="btn btn-g btn-sm" type="button" style="color:var(--acT)" @click="clearRecent()">پاک کردن</button></div>
          <template x-for="r in recent" :key="r">
            <div class="kbd-row"><x-icon i="clock" s="18" /><a :href="'{{ route('search') }}?q=' + encodeURIComponent(r)" style="flex:1;font-weight:600" :dir="/^[\x00-\x7F]+$/.test(r) ? 'ltr' : 'rtl'" :style="/^[\x00-\x7F]+$/.test(r) && 'text-align:right'" x-text="r"></a><button class="ib" type="button" style="width:40px;height:40px" aria-label="حذف از تاریخچه" @click="forget(r)"><x-icon i="x" s="16" /></button></div>
          </template>
        </div>
      </template>
      @if ($popular)
        <h2 class="t-h3" style="padding:24px 16px 12px">جست‌وجوهای پرتکرار</h2>
        <div class="chips" style="padding:0 16px">
          @foreach ($popular as $term)
            <a class="chip" href="{{ route('search', ['q' => $term]) }}"><span @class(['lat' => preg_match('/^[\x00-\x7F]+$/', $term)])>{{ $term }}</span></a>
          @endforeach
        </div>
      @endif
      <h2 class="t-h3" style="padding:24px 16px 12px">نمی‌دانید چه بخواهید؟</h2>
      <a class="kbd-row" href="{{ route('finder') }}"><span class="ico" style="width:28px;display:grid;place-items:center;color:var(--tx2)"><x-icon i="compass" s="20" /></span><b style="flex:1;font-size:15px">محصول مناسبم را پیدا کن</b><x-icon i="chevNext" s="18" /></a>
    </div>

    {{-- typing: live suggestions --}}
    <div x-show="q.trim() && q.trim() !== committed" x-cloak aria-live="polite">
      <template x-if="s && s.products.length">
        <div>
          <p class="glabel" style="padding:16px 16px 4px">محصولات</p>
          <template x-for="p in s.products" :key="p.url">
            <a class="kbd-row" :href="p.url" style="min-height:72px;gap:12px">
              <span class="ph" style="width:52px;height:52px;border-radius:50%;flex:none;overflow:hidden">
                <template x-if="p.img"><img :src="p.img" alt=""></template>
                <template x-if="!p.img"><svg class="art" width="46" height="46" viewBox="0 0 200 200" aria-hidden="true"><use :href="'#art-' + p.art"/></svg></template>
              </span>
              <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px"><b style="font-size:14px" x-text="p.name"></b>
                <span class="code" style="color:var(--tx2)"><span x-text="p.code[0]"></span><span class="hl" x-text="p.code[1]"></span><span x-text="p.code[2]"></span> · <span x-text="p.brand"></span></span></span>
              <x-icon i="chevNext" s="18" />
            </a>
          </template>
          <template x-if="s.links.length">
            <div>
              <p class="glabel" style="padding:20px 16px 4px">دسته و برند</p>
              <template x-for="l in s.links" :key="l.url">
                <a class="kbd-row" :href="l.url"><x-icon i="layers" s="20" /><span style="flex:1"><span x-text="l.pre"></span> <span class="lat" style="font-weight:800" x-text="l.brand"></span></span><span class="t-c mut3" x-text="l.n"></span></a>
              </template>
            </div>
          </template>
          <div style="padding:16px"><a class="btn btn-s btn-w" :href="s.all" @click="remember(q)"><span>مشاهده همه <span x-text="s.totalLabel"></span> نتیجه برای «<bdi x-text="q.trim()"></bdi>»</span></a></div>
        </div>
      </template>
      <template x-if="s && !s.products.length">
        <div class="empty" style="padding-top:40px">
          <p class="t-s mut">پیشنهادی برای «<bdi x-text="q.trim()"></bdi>» نیست. برای جست‌وجوی کامل، دکمه جست‌وجوی صفحه‌کلید را بزنید.</p>
        </div>
      </template>
    </div>

    {{-- submitted: full results --}}
    @if ($results !== null)
      <div x-show="!q.trim() || q.trim() === committed">
        @if ($results->isNotEmpty())
          <p class="glabel" style="padding:12px 16px 0">{{ Fa::digits($results->count()) }} نتیجه برای «<bdi>{{ $q }}</bdi>»</p>
          <div class="list-wrap g2">
            @foreach ($results as $p)
              <x-pcard :p="$p" s="120" />
            @endforeach
          </div>
        @else
          <div class="empty" style="padding-top:56px">
            <span class="ico"><x-icon i="noResult" s="32" /></span>
            <h2 class="t-h2">نتیجه‌ای برای «<bdi>{{ $q }}</bdi>» پیدا نشد</h2>
            <p class="t-s mut">املای کد مدل را بررسی کنید یا عبارت کوتاه‌تری بنویسید.</p>
          </div>
          @if ($maybe)
            <p class="glabel" style="padding:0 16px 10px">شاید منظورتان این بود</p>
            <div class="chips" style="padding:0 16px">
              @foreach ($maybe as $m)<a class="chip" href="{{ $m['url'] }}">{{ $m['label'] }}</a>@endforeach
            </div>
          @endif
          <div class="card" style="margin:28px 16px 0;padding:16px;gap:10px;background:var(--sf2)">
            <b style="font-size:14px">محصول را پیدا نکردید؟</b>
            <p class="t-s mut">کد یا عکس محصول را در واتساپ بفرستید؛ کارشناسان ما معادل مناسب را پیشنهاد می‌دهند.</p>
            <a class="btn btn-s btn-sm" href="https://wa.me/{{ preg_replace('/\D+/', '', (string) \App\Models\Setting::get('whatsapp_number', '')) }}?text={{ rawurlencode('سلام، دنبال این محصول هستم: ' . $q) }}" target="_blank" rel="noopener" style="align-self:flex-start"><x-icon i="chat" s="18" />پرسش در واتساپ</a>
          </div>
        @endif
      </div>
    @endif
  </div>

  @push('head')
  <script>
    function searchBox(q, url) {
      const load = () => { try { return JSON.parse(localStorage.getItem('fz-recent')) || []; } catch (e) { return []; } };
      return {
        q, committed: q.trim(), s: null, recent: load(), ctrl: null,
        init() { if (this.committed) this.remember(this.committed); },
        async suggest() {
          const t = this.q.trim();
          if (!t) { this.s = null; return; }
          this.ctrl?.abort(); this.ctrl = new AbortController();
          try { this.s = await (await fetch(url + '?q=' + encodeURIComponent(t), { signal: this.ctrl.signal })).json(); } catch (e) { /* offline: keep last */ }
        },
        persist() { try { localStorage.setItem('fz-recent', JSON.stringify(this.recent)); } catch (e) {} },
        remember(t) { t = t.trim(); if (!t) return; this.recent = [t, ...this.recent.filter((r) => r !== t)].slice(0, 5); this.persist(); },
        forget(t) { this.recent = this.recent.filter((r) => r !== t); this.persist(); },
        clearRecent() { this.recent = []; this.persist(); },
      };
    }
  </script>
  @endpush
</x-layouts.site>
