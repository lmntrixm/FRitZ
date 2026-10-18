{{-- Ink footer (M-Home): one line of copy, the WhatsApp action, links, inverse logo. --}}
<footer class="foot" {{ $attributes }}>
  <p style="font-size:22px;line-height:1.6;font-weight:700">برای انتخاب تجهیزات پروژه‌تان با ما گفت‌وگو کنید.</p>
  <a class="btn btn-p btn-w" href="https://wa.me/{{ preg_replace('/\D+/', '', (string) \App\Models\Setting::get('whatsapp_number', '')) }}" target="_blank" rel="noopener"><x-icon i="chat" s="20" />گفت‌وگو در واتساپ</a>
  <nav style="display:flex;flex-wrap:wrap;gap:0 24px;font-size:15px" aria-label="پیوندهای پانویس">
    <a href="{{ route('about') }}" style="min-height:44px;display:inline-flex;align-items:center">درباره و تماس</a>
    <a href="{{ route('home') }}#cats" style="min-height:44px;display:inline-flex;align-items:center">همه محصولات</a>
    <a href="{{ route('finder') }}" style="min-height:44px;display:inline-flex;align-items:center">راهنمای انتخاب</a>
  </nav>
  <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-top:12px">
    <span class="logo inv" style="padding:0"><span class="mk" style="width:150px;height:53px"></span><b>FRitZ</b></span>
    <p class="t-c lat" style="opacity:.7">© FRitZ</p>
  </div>
</footer>
