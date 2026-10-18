{{-- Sign-in (A-Login): error with attempts left, 15-minute lock after 5 failures, busy state. --}}
@extends('admin._auth', ['title' => 'ورود'])
@php use App\Support\Fa; @endphp
@section('form')
<form method="post" action="{{ route('admin.login') }}" style="width:min(400px,100%);display:flex;flex-direction:column;gap:18px" aria-label="ورود به پنل"
      x-data="{ busy: false, show: false, lock: {{ (int) $lockedFor }} }" x-init="if (lock) { const t = setInterval(() => { lock--; if (lock <= 0) clearInterval(t); }, 1000) }" @submit="busy = true">
  @csrf
  <div class="logo" style="padding:0"><span class="mk"></span><span style="display:flex;flex-direction:column;gap:3px"><b>FRitZ</b><small>پنل مدیریت</small></span></div>
  <div><h1 class="t-h1">ورود به پنل مدیریت</h1><p class="t-s mut">کاتالوگ آنلاین محصولات FRitZ Smart Solutions</p></div>

  <template x-if="lock > 0">
    <div class="msgbox wn" role="alert"><x-icon i="lock" s="18" /><span>پس از ۵ تلاش ناموفق، ورود برای ۱۵ دقیقه قفل شد. زمان باقی‌مانده: <b class="lat" x-text="String(Math.floor(lock / 60)).padStart(2, '0') + ':' + String(lock % 60).padStart(2, '0')"></b></span></div>
  </template>
  @if ($errors->has('password') && session('left') > 0)
    <div class="msgbox er" role="alert"><x-icon i="alert" s="18" /><span>ایمیل یا رمز عبور درست نیست. {{ Fa::digits(session('left')) }} تلاش دیگر باقی مانده است.</span></div>
  @elseif ($errors->has('email'))
    <div class="msgbox er" role="alert"><x-icon i="alert" s="18" /><span>{{ $errors->first('email') }}</span></div>
  @endif

  <div class="fld"><label class="lab" for="email">ایمیل</label>
    <input id="email" name="email" type="email" class="inp ltr" value="{{ old('email') }}" autocomplete="username" required autofocus :readonly="busy" dir="ltr"></div>
  <div class="fld"><label class="lab" for="password">رمز عبور</label>
    <div style="display:flex;gap:8px">
      <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" @class(['inp', 'ltr', 'err' => $errors->has('password')]) autocomplete="current-password" required :readonly="busy" dir="ltr" @if($errors->has('password')) aria-invalid="true" aria-describedby="pw-err" @endif>
      <button type="button" class="ib ib-b" :aria-label="show ? 'پنهان کردن رمز' : 'نمایش رمز'" :aria-pressed="show" @click="show = !show"><x-icon i="eye" s="18" /></button>
    </div>
    @error('password')<span class="emsg" id="pw-err">{{ $message }}</span>@enderror
  </div>
  <label class="opt" style="font-size:13px"><input type="checkbox" class="cbx" name="remember" value="1" @checked(old('remember', true))>مرا به خاطر بسپار</label>
  <button type="submit" class="btn btn-p btn-lg btn-w" :disabled="busy || lock > 0">
    <span x-show="busy" x-cloak class="sk" style="width:16px;height:16px;border-radius:50%;background:var(--acI);opacity:.35"></span>
    <span x-text="busy ? 'در حال ورود…' : 'ورود'">ورود</span>
  </button>
  <p class="t-c mut3">حساب ندارید یا رمز را فراموش کرده‌اید؟ از مدیر کل بخواهید شما را دعوت کند یا رمز را بازنشانی کند.</p>
</form>
@endsection
@section('aside')
  <b style="font-size:24px;line-height:1.5">{{ Fa::digits($products) }} محصول، یک منبع حقیقت.</b>
  <p class="t-s mut" style="margin-top:4px">محصولات، فیلترها، رسانه و به‌روزرسانی‌های کاتالوگ عمومی از همین‌جا مدیریت می‌شوند.</p>
@endsection
