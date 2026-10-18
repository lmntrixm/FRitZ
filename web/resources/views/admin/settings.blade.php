{{-- Settings (A-Settings), «مدیر کل» only. Logo and the .fritz key arrive with the update phase. --}}
@php
  $sample = ['اقلام' => "۱. تاچ پنل ۷ اینچ MixPad Matter\n    کد \u{2066}MIXPAD-7M\u{2069} — ۲ عدد", 'نام' => 'علی رضایی', 'پروژه' => 'ویلای دو طبقه، لواسان', 'تاریخ' => \App\Support\Fa::date(now(), 'd MMMM y')];
  $val = fn ($k) => old($k, $s[$k] ?? '');
@endphp
<x-layouts.admin title="تنظیمات" active="settings">
  <x-slot:actions>
    <button class="btn btn-p btn-sm" type="submit" form="settings">ذخیره تغییرات</button>
  </x-slot:actions>

  @if ($errors->any())
    <div class="msgbox er" role="alert"><x-icon i="alert" s="18" /><span><b>{{ \App\Support\Fa::digits($errors->count()) }} مورد ذخیره نشد.</b> {{ $errors->first() }}</span></div>
  @endif

  <form id="settings" method="post" action="{{ route('admin.settings') }}" style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start"
        x-data="settingsForm(@js($val('message_template')), @js($sample))" @input="dirty = true" @submit="dirty = false">
    @csrf @method('put')
    <nav class="qnav panel" style="flex:0 1 220px;min-width:200px;padding:8px" aria-label="بخش‌های تنظیمات">
      <a href="#wa" class="on"><x-icon i="chat" s="16" />واتساپ و پیام</a>
      <a href="#contact"><x-icon i="phone" s="16" />اطلاعات تماس</a>
      <a href="#seo"><x-icon i="globe" s="16" />سئو</a>
    </nav>

    <div style="flex:999 1 560px;min-width:0;display:flex;flex-direction:column;gap:16px">
      <section class="panel" id="wa"><div class="panel-h"><h2>واتساپ و قالب پیام استعلام</h2></div><div class="panel-b" style="display:flex;flex-direction:column;gap:14px">
        <div class="frow">
          <div class="fld"><label class="lab" for="whatsapp_number">شماره واتساپ استعلام</label>
            <input id="whatsapp_number" name="whatsapp_number" @class(['inp', 'ltr', 'err' => $errors->has('whatsapp_number')]) dir="ltr" inputmode="tel" placeholder="+98 912 000 0000" value="{{ $val('whatsapp_number') ? '+' . $val('whatsapp_number') : '' }}">
            @error('whatsapp_number')<span class="emsg">{{ $message }}</span>@else<span class="hint">با کد کشور. استعلام‌ها به این شماره ارسال می‌شوند. خالی باشد، واتساپ صفحه انتخاب مخاطب را باز می‌کند.</span>@enderror</div>
          <div class="fld"><label class="lab" for="whatsapp_name">نام نمایشی</label><input id="whatsapp_name" name="whatsapp_name" class="inp" value="{{ $val('whatsapp_name') }}" placeholder="فروش FRitZ"></div>
        </div>
        <div class="fld"><label class="lab" for="message_template">قالب متن پیام</label>
          <textarea id="message_template" name="message_template" x-ref="tpl" x-model="tpl" @class(['inp', 'err' => $errors->has('message_template')]) rows="8" required></textarea>
          <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center"><span class="hint">متغیرها:</span>
            @foreach (array_keys($sample) as $v)<button type="button" class="chip" style="height:30px;font-size:12px" @click="insert('{{ '{' . $v . '}' }}')">{{ '{' . $v . '}' }}</button>@endforeach
          </div>
          @error('message_template')<span class="emsg">{{ $message }}</span>@else<span class="hint">خطی که متغیرش خالی باشد (مثلاً نام وارد نشده) از پیام حذف می‌شود.</span>@enderror
        </div>
        <div class="fld"><span class="lab">پیش‌نمایش</span><div class="msg" style="max-width:420px" x-text="preview" aria-live="polite"></div>
          <span class="hint" x-show="!tpl.includes('{اقلام}')" x-cloak style="color:var(--err);font-weight:700">بدون {اقلام} فهرست محصولات در پیام نمی‌آید.</span></div>
      </div></section>

      <section class="panel" id="contact"><div class="panel-h"><h2>اطلاعات تماس</h2></div><div class="panel-b">
        <p class="hint" style="margin-bottom:14px">در صفحه «درباره و تماس» کاتالوگ نمایش داده می‌شود.</p>
        <div class="frow">
          <div class="fld"><label class="lab" for="phone">تلفن</label><input id="phone" name="phone" class="inp ltr" dir="ltr" inputmode="tel" value="{{ $val('phone') }}" placeholder="+98 21 0000 0000"></div>
          <div class="fld"><label class="lab" for="email">ایمیل</label><input id="email" name="email" type="email" @class(['inp', 'ltr', 'err' => $errors->has('email')]) dir="ltr" value="{{ $val('email') }}" placeholder="info@example.com">@error('email')<span class="emsg">{{ $message }}</span>@enderror</div>
          <div class="fld"><label class="lab" for="hours">ساعات پاسخ‌گویی</label><input id="hours" name="hours" class="inp" value="{{ $val('hours') }}" placeholder="شنبه تا چهارشنبه، ۹ تا ۱۸"></div>
        </div>
        <div class="fld" style="margin-top:14px"><label class="lab" for="address">نشانی</label><input id="address" name="address" class="inp" value="{{ $val('address') }}" placeholder="نشانی دفتر"></div>
      </div></section>

      <section class="panel" id="seo" x-data="{ t: @js($val('seo_title')), d: @js($val('seo_description')) }"><div class="panel-h"><h2>سئو</h2></div><div class="panel-b" style="display:flex;flex-direction:column;gap:14px">
        <div class="fld"><label class="lab" for="seo_title">عنوان سایت</label><input id="seo_title" name="seo_title" class="inp" x-model="t" required maxlength="120">
          <span class="hint" :style="t.length > 60 && 'color:var(--wn);font-weight:700'" x-text="FA(t.length) + ' نویسه · پیشنهاد: کمتر از ۶۰'"></span></div>
        <div class="fld"><label class="lab" for="seo_description">توضیح متا</label><textarea id="seo_description" name="seo_description" class="inp" rows="3" x-model="d" maxlength="300"></textarea>
          <span class="hint" :style="d.length > 160 && 'color:var(--wn);font-weight:700'" x-text="FA(d.length) + ' نویسه · ' + (d.length > 160 ? 'کمی بلندتر از ۱۶۰' : 'پیشنهاد: تا ۱۶۰')"></span></div>
        <label class="swl" style="padding:4px 14px;background:var(--sf2)"><span>اجازه ایندکس به موتورهای جست‌وجو<br><span class="hint">روی دامنه تست خاموش بماند تا نسخه آزمایشی در گوگل نیاید.</span></span>
          <input type="checkbox" class="sw" name="allow_indexing" value="1" @checked(old('allow_indexing', $s['allow_indexing']))></label>
      </div></section>
    </div>

    <div class="savebar" style="flex-basis:100%;margin:0 -32px -64px" x-show="dirty" x-cloak>
      <span class="t-s" style="font-weight:700;flex:1">تغییرات ذخیره‌نشده</span>
      <a class="btn btn-g btn-sm" href="{{ route('admin.settings') }}" @click="dirty = false">انصراف</a>
      <button class="btn btn-p btn-sm" type="submit">ذخیره تغییرات</button>
    </div>
  </form>

  @push('head')
  <script>
    function settingsForm(tpl, sample) {
      return {
        tpl, dirty: false,
        init() { window.addEventListener('beforeunload', (e) => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } }); },
        insert(v) {
          const el = this.$refs.tpl, a = el.selectionStart ?? this.tpl.length, b = el.selectionEnd ?? a;
          this.tpl = this.tpl.slice(0, a) + v + this.tpl.slice(b);
          this.dirty = true;
          this.$nextTick(() => { el.focus(); el.setSelectionRange(a + v.length, a + v.length); });
        },
        get preview() {
          return this.tpl.split('\n')
            .filter((l) => !Object.keys(sample).some((k) => l.includes('{' + k + '}') && !sample[k]))
            .map((l) => l.replace(/\{([^}]+)\}/g, (m, k) => (k in sample ? sample[k] : m)))
            .join('\n');
        },
      };
    }
  </script>
  @endpush
</x-layouts.admin>
