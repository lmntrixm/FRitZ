{{-- First run: create the first «مدیر کل». Shown only while there are no users. --}}
@extends('admin._auth', ['title' => 'راه‌اندازی'])
@section('form')
<form method="post" action="{{ route('admin.setup') }}" style="width:min(400px,100%);display:flex;flex-direction:column;gap:18px">
  @csrf
  <div class="logo" style="padding:0"><span class="mk"></span><span style="display:flex;flex-direction:column;gap:3px"><b>FRitZ</b><small>پنل مدیریت</small></span></div>
  <div><h1 class="t-h1">ساخت حساب مدیر کل</h1><p class="t-s mut">این صفحه فقط یک بار، پیش از ساخت اولین کاربر، باز است. کاربران بعدی را مدیر کل از بخش «کاربران» دعوت می‌کند.</p></div>
  @if ($errors->any())
    <div class="msgbox er" role="alert"><x-icon i="alert" s="18" /><span>{{ $errors->first() }}</span></div>
  @endif
  @if ($needsKey)
    <div class="fld"><label class="lab" for="key">کلید راه‌اندازی</label><input id="key" name="key" type="password" class="inp ltr" dir="ltr" required autocomplete="off">
      <span class="hint">همان مقدار <span class="lat">DEPLOY_TOKEN</span> در فایل <span class="lat">.env</span> روی هاست.</span></div>
  @endif
  <div class="fld"><label class="lab" for="name">نام و نام خانوادگی</label><input id="name" name="name" class="inp" value="{{ old('name') }}" required autocomplete="name"></div>
  <div class="fld"><label class="lab" for="email">ایمیل</label><input id="email" name="email" type="email" class="inp ltr" dir="ltr" value="{{ old('email') }}" required autocomplete="username"></div>
  <div class="fld"><label class="lab" for="password">رمز عبور</label><input id="password" name="password" type="password" class="inp ltr" dir="ltr" required minlength="10" autocomplete="new-password"><span class="hint">دست‌کم ۱۰ نویسه.</span></div>
  <div class="fld"><label class="lab" for="password_confirmation">تکرار رمز عبور</label><input id="password_confirmation" name="password_confirmation" type="password" class="inp ltr" dir="ltr" required autocomplete="new-password"></div>
  <button type="submit" class="btn btn-p btn-lg btn-w">ساخت حساب و ورود</button>
</form>
@endsection
@section('aside')
  <b style="font-size:24px;line-height:1.5">راه‌اندازی پنل FRitZ</b>
  <p class="t-s mut" style="margin-top:4px">پس از ورود، شماره واتساپ و اطلاعات تماس را در «تنظیمات» وارد کنید.</p>
@endsection
