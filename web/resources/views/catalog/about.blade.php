{{-- About and contact (M-About). Photos of completed projects stay labelled grey slots until the client supplies them. --}}
@php
  use App\Models\Setting;
  $wa = preg_replace('/\D+/', '', (string) Setting::get('whatsapp_number', ''));
  $services = [
        ['چند برند، چند پروتکل', \App\Support\Fa::digits($brands->count()) . ' برند در ' . $protocols->pluck('name')->join('، ', ' و ') . ' کنار هم؛ مقایسه بی‌طرف بر پایه دیتاشیت.'],
        ['نمایندگی رسمی', 'تجهیزات از مسیر رسمی برندها تأمین می‌شود.'],
        ['مشاوره فنی پیش از خرید', 'ترکیب سازگار تجهیزات بر اساس نقشه و نیاز پروژه.'],
        ['اجرا و راه‌اندازی کامل', 'نصب، برنامه‌ریزی و تحویل سناریوها به کارفرما.'],
      ];
@endphp
<x-layouts.site title="درباره و تماس">
  <x-slot:header><x-hdr title="درباره و تماس" :search="false" /></x-slot:header>

  <section style="padding:28px 20px 0;display:flex;flex-direction:column;gap:14px">
    <div class="logo lg" style="padding:0"><span class="mk"></span><b>FRitZ</b></div>
    <h1 class="t-h1" style="margin-top:8px">از انتخاب تجهیزات تا راه‌اندازی، کنار پروژه شما.</h1>
    <p class="t-b mut">FRitZ تجهیزات خانه و ساختمان هوشمند را از برندهای معتبر تأمین و اجرا می‌کند. کاتالوگ را ببینید، لیست استعلام بسازید و کارشناسان ما بهترین ترکیب را برای پروژه‌تان پیشنهاد می‌دهند.</p>
  </section>

  <section class="sec">
    @forelse ($projects as $pr)
      <figure class="ph" style="height:260px;margin:0 -20px 12px">
        @if ($pr->media)<img src="{{ $pr->media->url() }}" alt="{{ $pr->title }}" loading="lazy" style="object-fit:cover">@else<span class="slot-note">عکس پروژه اجراشده</span>@endif
      </figure>
      <p class="t-c mut3" style="margin-bottom:20px">{{ $pr->title }}@if($pr->location) · {{ $pr->location }}@endif</p>
    @empty
      <div class="ph" style="height:260px;margin:0 -20px 32px"><span class="slot-note">عکس پروژه اجراشده</span></div>
    @endforelse
    <div style="display:flex;flex-direction:column;gap:28px">
      @foreach ($services as [$t, $d])
        <div style="display:flex;flex-direction:column;gap:6px"><h2 class="t-h2">{{ $t }}</h2><p class="t-b mut">{{ $d }}</p></div>
      @endforeach
    </div>
  </section>

  <section class="sec">
    <h2 class="t-h2" style="margin-bottom:14px">پروتکل‌ها و برندها</h2>
    <div class="pbs" style="gap:6px">@foreach ($protocols as $x)<span class="pb">{{ $x->name }}</span>@endforeach</div>
    <p class="lat" style="margin-top:16px;font-size:22px;line-height:1.7;font-weight:700;color:var(--tx3);text-align:right">{{ $brands->pluck('name')->join(' ') }}</p>
  </section>

  <section class="sec">
    <h2 class="t-h2" style="margin-bottom:14px">ارتباط با ما</h2>
    <a class="btn btn-p btn-lg btn-w" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><x-icon i="chat" s="20" sw="1.8" />گفت‌وگو در واتساپ</a>
    @php
      $rows = array_filter([
        ['phone', 'تلفن', Setting::get('phone'), 'tel:' . preg_replace('/[^\d+]/', '', (string) Setting::get('phone')), true],
        ['mail', 'ایمیل', Setting::get('email'), 'mailto:' . Setting::get('email'), true],
        ['clock', 'ساعات پاسخ‌گویی', Setting::get('hours'), null, false],
        ['pin', 'نشانی دفتر', Setting::get('address'), null, false],
      ], fn ($r) => filled($r[2]));
    @endphp
    @if ($rows)
      <div class="card" style="margin-top:12px;padding:0 20px;background:var(--sf2)">
        @foreach ($rows as [$icon, $label, $value, $href, $lat])
          <{{ $href ? 'a' : 'div' }} class="kbd-row" @if($href) href="{{ $href }}" @endif style="padding:0"><x-icon :i="$icon" s="20" /><span style="flex:1">{{ $label }}</span><span @class(['lat' => $lat]) style="font-weight:700">{{ $value }}</span></{{ $href ? 'a' : 'div' }}>
        @endforeach
      </div>
    @endif
    <div class="ph" style="height:160px;margin-top:12px"><span class="slot-note">[نقشه موقعیت دفتر]</span></div>
  </section>

  <x-slot:footer>
    <footer class="foot"><div style="display:flex;align-items:flex-end;justify-content:space-between"><span class="logo inv" style="padding:0"><span class="mk" style="width:150px;height:53px"></span><b>FRitZ</b></span><p class="t-c lat" style="opacity:.7">© FRitZ</p></div></footer>
  </x-slot:footer>
</x-layouts.site>
