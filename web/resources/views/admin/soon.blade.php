{{-- A section that is designed (project/A-*) but not built yet. --}}
<x-layouts.admin :title="$title" :active="$key">
  <section class="panel">
    <div class="soon">
      <span class="ico"><x-icon :i="$icon" s="30" /></span>
      <h2 class="t-h2">بخش «{{ $title }}» در مرحله بعد ساخته می‌شود</h2>
      <p class="t-s mut" style="max-width:460px">طراحی این بخش آماده است و طبق نقشه کار (docs/PLAN.md) پیاده‌سازی می‌شود. فعلاً «نمای کلی» و «تنظیمات» فعال‌اند.</p>
      <a class="btn btn-s btn-sm" href="{{ route('admin.overview') }}">بازگشت به نمای کلی</a>
    </div>
  </section>
</x-layouts.admin>
