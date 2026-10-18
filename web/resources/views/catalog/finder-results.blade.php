{{-- Finder results (M-Wizard, last screen): choices as chips, a small scoped filter, suggestions grouped by need with the reason. --}}
@php use App\Support\Fa; @endphp
<x-layouts.site title="پیشنهاد برای شما" stick>
  <div style="display:flex;align-items:center;gap:4px;padding:8px">
    <a class="ib" href="{{ route('finder') }}" aria-label="بازگشت به سؤال‌ها"><x-icon i="chevBack" /></a>
    <span class="t-s" style="flex:1;text-align:center;font-weight:700">پیشنهاد برای شما</span>
    <a class="ib" href="{{ route('home') }}" aria-label="بستن"><x-icon i="x" /></a>
  </div>

  <div style="padding:8px 16px 16px">
    <div class="chips" style="gap:6px">
      @foreach ($chips as $c)
        <span class="chip" style="height:32px;font-size:13px"><span @class(['lat' => preg_match('/^[\x00-\x7F]+$/', $c)])>{{ $c }}</span></span>
      @endforeach
      <a class="chip" href="{{ route('finder') }}" style="height:30px;font-size:12px;background:transparent;color:var(--acT);font-weight:800">ویرایش</a>
    </div>

    @foreach ($cards as $card)
      <div class="card fcard" style="margin-top:16px;padding:18px;gap:14px;background:var(--sf2)">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px"><b style="font-size:14px;display:inline-flex;align-items:center;gap:6px"><x-icon i="filter" s="16" />{{ $card['title'] }}</b><span class="t-c mut3">فقط برای همین انتخاب‌ها</span></div>
        @foreach ($card['groups'] as $g)
          <div style="display:flex;flex-direction:column;gap:6px">
            <span class="t-c mut" style="font-weight:700">{{ $g['title'] }}</span>
            <div class="chips" style="gap:6px">
              @foreach ($g['opts'] as $o)
                <a @class(['chip', 'on' => $o['on']]) href="{{ $o['url'] }}" style="height:34px;padding:0 12px" @if($o['on']) aria-current="true" @endif>
                  @if ($o['swatch'])<span class="sw" style="background:{{ $o['swatch'] }}"></span>@endif{{ $o['label'] }}
                </a>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    @endforeach

    @foreach ($groups as $gr)
      <div style="display:flex;align-items:baseline;justify-content:space-between;margin-top:20px;margin-bottom:10px">
        <h2 class="t-h3" style="display:inline-flex;align-items:center;gap:6px"><x-icon :i="$gr['need']->icon ?: 'box'" s="18" />{{ $gr['need']->label }}</h2>
        <span class="t-c mut3">{{ $gr['sub'] }}</span>
      </div>
      @if ($gr['items']->isEmpty())
        <div class="alert"><x-icon i="info" s="18" /><span>برای این انتخاب‌ها محصولی در کاتالوگ نیست. انتخاب‌ها را ویرایش کنید یا از کارشناسان بپرسید.</span></div>
      @endif
      <div style="display:flex;flex-direction:column;gap:28px">
        @foreach ($gr['items'] as $p)
          @php $card = $p->card(); @endphp
          <article class="card" style="gap:12px">
            <a href="{{ $card['url'] }}" style="display:flex;gap:12px;color:inherit">
              <span class="ph" style="width:72px;height:72px;flex:none"><x-pimg :p="$p" s="64" /></span>
              <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px"><span class="brand">{{ $p->brand->name }}</span><b style="font-size:14px;line-height:1.6">{{ $p->name }}</b><span class="code">{{ $p->model_code }}</span></span>
            </a>
            @if ($p->pitch)
              <div class="why"><x-icon i="check" s="16" sw="2" /><span>{{ $p->pitch }}</span></div>
            @endif
            <button type="button" class="pc-add" :class="$store.inq.has({{ $p->id }}) && 'in'" @click="$store.inq.toggle(@js($card))">
              <span x-show="!$store.inq.has({{ $p->id }})"><x-icon i="plus" s="16" sw="2" /></span><span x-show="$store.inq.has({{ $p->id }})" x-cloak><x-icon i="check" s="16" sw="2" /></span>
              <span x-text="$store.inq.has({{ $p->id }}) ? 'در لیست استعلام' : 'افزودن به استعلام'">افزودن به استعلام</span>
            </button>
          </article>
        @endforeach
      </div>
    @endforeach
  </div>

  <div class="stick" style="flex-direction:column;gap:8px">
    @if ($all->isNotEmpty())
      <button class="btn btn-p btn-w" type="button" @click="$store.inq.addAll(@js($all->map->card()->values()))"><x-icon i="plus" s="20" sw="2" />افزودن هر {{ Fa::digits($all->count()) }} به لیست استعلام</button>
    @endif
    <a class="btn btn-g btn-sm btn-w" href="{{ $listUrl }}" style="color:var(--acT)">مشاهده همه نتایج در فیلتر<x-icon i="chevNext" s="16" /></a>
  </div>
</x-layouts.site>
