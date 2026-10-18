{{-- Update (A-Update, A-FlowUpdate step 1): an encrypted .fritz package or a plain Excel sheet, then history. «مدیر کل» only. --}}
@php
  use App\Support\Fa;
  $tabs = ['fritz' => ['فایل رمزنگاری‌شده', 'lock'], 'xlsx' => ['ورود از اکسل', 'table'], 'history' => ['تاریخچه به‌روزرسانی‌ها', 'history']];
  $status = ['applied' => ['st pub', 'موفق'], 'failed' => ['st er', 'ناموفق، هیچ تغییری اعمال نشد'], 'rolled_back' => ['st draft', 'بازگردانی‌شده']];
@endphp
<x-layouts.admin title="به‌روزرسانی محصولات" active="update" sub="افزودن و ویرایش گروهی محصولات با یک فایل. پیش از اعمال، همه تغییرها را می‌بینید و آخرین به‌روزرسانی قابل بازگردانی است.">
  <x-slot:actions><span class="int"><x-icon i="lock" s="12" sw="2" />ویژه مدیر کل</span></x-slot:actions>

  <nav class="tabs" aria-label="نوع به‌روزرسانی">
    @foreach ($tabs as $k => [$label, $icon])
      <a @class(['tab', 'on' => $tab === $k]) href="{{ route('admin.update.index', ['tab' => $k]) }}" @if($tab === $k) aria-current="page" @endif style="text-decoration:none"><x-icon :i="$icon" s="16" />{{ $label }}</a>
    @endforeach
  </nav>

  @if ($pending && $tab !== 'history')
    <div class="msgbox wn"><x-icon i="clock" s="18" /><span style="flex:1"><b>یک فایل بررسی شده و منتظر تأیید شماست:</b> <bdi class="lat">{{ $pending->file_name }}</bdi>. تا تأیید نکنید، هیچ تغییری در کاتالوگ اعمال نمی‌شود.</span>
      <a class="btn btn-s btn-sm" href="{{ route('admin.update.show', $pending) }}">ادامه بررسی</a></div>
  @endif

  @if ($tab === 'history')
    <section class="panel">
      <div class="panel-h"><h2>تاریخچه به‌روزرسانی‌ها</h2><span class="t-c mut3">فقط آخرین به‌روزرسانی اعمال‌شده قابل بازگردانی است</span></div>
      @if ($history->isEmpty())
        <div class="soon" style="padding:48px 24px"><span class="ico"><x-icon i="history" s="26" /></span><b>هنوز به‌روزرسانی‌ای اعمال نشده است</b><span class="t-s mut">پس از اولین فایل، تاریخچه و امکان بازگردانی اینجا دیده می‌شود.</span></div>
      @else
        <div class="tw"><table class="tbl"><thead><tr><th>تاریخ</th><th>کاربر</th><th>فایل</th><th>تغییرات</th><th>وضعیت</th><th><span class="sr">کارها</span></th></tr></thead><tbody>
          @foreach ($history as $h)
            @php [$sc, $sl] = $status[$h->status] ?? ['st draft', $h->status]; @endphp
            <tr>
              <td class="t-c">{{ Fa::date($h->applied_at ?? $h->created_at, 'd MMMM y') }}<br><span class="mut3">{{ Fa::digits(($h->applied_at ?? $h->created_at)->format('H:i')) }}</span></td>
              <td><span class="t-s">{{ $h->uploader?->name ?? '—' }}</span></td>
              <td><span style="display:inline-flex;gap:6px;align-items:center"><x-icon :i="$h->kind === 'fritz' ? 'lock' : 'table'" s="14" /><span class="code" style="color:var(--tx);font-size:12px">{{ $h->file_name }}</span></span></td>
              <td><span style="display:inline-flex;gap:4px;align-items:center;font-size:12px"><span class="dm add" aria-label="جدید">+</span>{{ Fa::digits($h->added) }}<span class="dm mod" aria-label="ویرایش" style="margin-inline-start:6px">~</span>{{ Fa::digits($h->modified) }}@if ($h->diff['report']['deleted'] ?? 0)<span class="dm del" aria-label="حذف" style="margin-inline-start:6px">−</span>{{ Fa::digits($h->diff['report']['deleted']) }}@endif</span></td>
              <td><span class="{{ $sc }}">{{ $sl }}</span></td>
              <td><span style="display:inline-flex">
                <a class="ib" style="width:36px;height:36px" href="{{ route('admin.update.show', $h) }}" aria-label="جزئیات و گزارش" title="جزئیات و گزارش"><x-icon i="eye" s="16" /></a>
                <a class="ib" style="width:36px;height:36px" href="{{ route('admin.update.file', $h) }}" aria-label="دانلود فایل اصلی" title="دانلود فایل اصلی"><x-icon i="download" s="16" /></a>
              </span></td>
            </tr>
          @endforeach
        </tbody></table></div>
      @endif
    </section>
  @else
    <ol class="stepper" style="list-style:none;margin:0;padding:0" aria-label="مراحل">
      @foreach (['آپلود', 'اعتبارسنجی', 'پیش‌نمایش تغییرات', 'گزینه‌ها', 'تأیید و اعمال'] as $i => $s)
        <li @class(['step', 'cur' => $i === 0]) @if($i === 0) aria-current="step" @endif><span class="n">{{ Fa::digits($i + 1) }}</span>{{ $s }}</li>
      @endforeach
    </ol>

    <div class="g2a" style="align-items:start">
      @if ($tab === 'fritz' && ! $hasKey)
        <section class="panel"><div class="panel-h"><h2>بارگذاری بسته <span class="lat">.fritz</span></h2><span class="st wn">نیاز به رمز</span></div><div class="panel-b" style="display:flex;flex-direction:column;gap:12px">
          <div class="msgbox wn"><x-icon i="key" s="18" /><span><b>هنوز رمز بسته تعیین نشده است.</b> فایل‌های <span class="lat">.fritz</span> با رمزی باز می‌شوند که در «تنظیمات» تعیین می‌کنید. همان رمز را به سازنده فایل‌ها بدهید.</span></div>
          <a class="btn btn-p btn-sm" style="align-self:flex-start" href="{{ route('admin.settings') }}#key"><x-icon i="key" s="16" />تعیین رمز در تنظیمات</a>
        </div></section>
      @else
        <form class="panel" method="post" action="{{ route('admin.update.upload') }}" enctype="multipart/form-data"
              x-data="dropZone({{ 20 * 1024 * 1024 }}, @js($tab === 'fritz' ? ['fritz'] : ['xlsx']))" @submit="busy = true">
          @csrf
          <input type="hidden" name="kind" value="{{ $tab }}">
          <div class="panel-h"><h2>{{ $tab === 'fritz' ? 'بارگذاری بسته رمزنگاری‌شده' : 'بارگذاری فایل اکسل' }}</h2><span class="t-c mut3">مرحله ۱ از ۵</span></div>
          <div class="panel-b" style="display:flex;flex-direction:column;gap:12px">
            @if ($tab === 'fritz')
              <div class="msgbox in"><x-icon i="lock" s="16" /><span>فایل با رمزنگاری <b class="lat">AES-256-GCM</b> محافظت می‌شود و با رمز ثبت‌شده در تنظیمات باز می‌شود. اثر انگشت رمز: <b class="lat">{{ $keyMeta['fp'] ?? '' }}</b></span></div>
            @endif
            <label @class(['drop', 'err' => $errors->has('file')]) :class="{ over: over, err: err || {{ $errors->has('file') ? 'true' : 'false' }} && !name }"
                   @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="drop($event)" style="cursor:pointer">
              <span class="ico" @if($errors->has('file')) style="color:var(--err)" @endif><x-icon :i="$tab === 'fritz' ? 'lock' : 'table'" s="26" /></span>
              <b x-show="!name">{{ $tab === 'fritz' ? 'فایل .fritz را اینجا رها کنید' : 'فایل اکسل را اینجا رها کنید' }}</b>
              <b x-show="name" x-cloak class="lat" x-text="name"></b>
              <span class="t-s mut" x-show="!name"><span class="lat">{{ $tab === 'fritz' ? '.fritz' : '.xlsx' }}</span> · حداکثر ۲۰ مگابایت{{ $tab === 'xlsx' ? ' · ستون‌ها مطابق قالب نمونه' : '' }}</span>
              <span class="t-s mut" x-show="name" x-cloak x-text="size"></span>
              <span class="btn btn-s btn-sm">انتخاب فایل</span>
              <input class="sr" type="file" name="file" x-ref="file" accept="{{ $tab === 'fritz' ? '.fritz' : '.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }}" required @change="pick()">
            </label>
            <span class="emsg" x-show="err" x-cloak x-text="err"></span>
            @error('file')<div class="msgbox er" role="alert" x-show="!name"><x-icon i="alert" s="18" /><span>{{ $message }}</span></div>@enderror
            <button class="btn btn-p btn-sm" type="submit" style="align-self:flex-start" :disabled="!name || err || busy" :class="(!name || err) && 'is-dis'">
              <x-icon :i="$tab === 'fritz' ? 'key' : 'check'" s="16" /><span x-text="busy ? 'در حال بررسی…' : '{{ $tab === 'fritz' ? 'رمزگشایی و بررسی' : 'بررسی فایل' }}'"></span></button>
            <span class="hint">بررسی هیچ تغییری در کاتالوگ نمی‌دهد. در مرحله بعد همه تغییرها را می‌بینید و تصمیم می‌گیرید.</span>
          </div>
        </form>
      @endif

      <div style="display:flex;flex-direction:column;gap:16px">
        <section class="panel"><div class="panel-h"><h2>فایل را از کجا بیاورم؟</h2></div><div class="panel-b" style="display:flex;flex-direction:column;gap:12px">
          <p class="t-s">بهترین شروع، خروجی همین کاتالوگ است: فایل را دانلود کنید، در اکسل ویرایش کنید و برگردانید. ردیف تازه یعنی محصول جدید.</p>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-s btn-sm" href="{{ route('admin.update.export') }}"><x-icon i="download" s="16" />خروجی اکسل همه محصولات</a>
            <a class="btn btn-g btn-sm" style="color:var(--acT)" href="{{ route('admin.update.export', ['empty' => 1]) }}"><x-icon i="download" s="16" />قالب اکسل خالی</a>
          </div>
          <ul class="t-s mut" style="margin:0;padding-inline-start:18px;display:flex;flex-direction:column;gap:4px">
            <li>محصول با «کد مدل» شناخته می‌شود؛ کد را تغییر ندهید.</li>
            <li>ستونی که در فایل نباشد، دست نمی‌خورد. خانه خالیِ یک ستون موجود، آن فیلد را پاک می‌کند.</li>
            <li>چند مقدار را با «،» جدا کنید (مثل پروتکل: <bdi class="lat">KNX، Zigbee</bdi>).</li>
            <li>عکس، دیتاشیت و «سازگار با» از فایل تغییر نمی‌کنند؛ آن‌ها را در فرم محصول ویرایش کنید.</li>
          </ul>
        </div></section>
        @if ($tab === 'fritz')
          <section class="panel"><div class="panel-h"><h2>ساخت فایل <span class="lat">.fritz</span></h2></div><div class="panel-b" style="display:flex;flex-direction:column;gap:12px">
            <p class="t-s">سازنده فایل‌ها اکسل را با ابزار زیر و همان رمز به <span class="lat">.fritz</span> تبدیل می‌کند. ابزار یک فایل <span class="lat">HTML</span> است که بدون اینترنت در مرورگر کار می‌کند و فایل را جایی نمی‌فرستد.</p>
            <a class="btn btn-s btn-sm" style="align-self:flex-start" href="{{ route('admin.update.packer') }}"><x-icon i="download" s="16" />دانلود ابزار ساخت بسته</a>
          </div></section>
        @endif
      </div>
    </div>
  @endif

  @push('head')
  <script>
    function dropZone(max, exts) {
      return {
        name: '', size: '', err: '', over: false, busy: false,
        drop(e) {
          this.over = false;
          if (!e.dataTransfer.files.length) return;
          this.$refs.file.files = e.dataTransfer.files;
          this.pick();
        },
        pick() {
          const f = this.$refs.file.files[0];
          this.err = '';
          if (!f) { this.name = ''; return; }
          this.name = f.name;
          this.size = f.size >= 1048576 ? FA((f.size / 1048576).toFixed(1)) + ' مگابایت' : FA(Math.max(1, Math.round(f.size / 1024))) + ' کیلوبایت';
          const ext = (f.name.split('.').pop() || '').toLowerCase();
          if (!exts.includes(ext)) this.err = exts[0] === 'fritz' ? 'این فایل .fritz نیست. فایل اکسل را از تب «ورود از اکسل» بارگذاری کنید.' : 'فقط فایل .xlsx پذیرفته می‌شود. فایل .xls یا .csv را در اکسل با «Save As → Excel Workbook» دوباره ذخیره کنید.';
          else if (f.size > max) this.err = 'فایل بزرگ‌تر از حد مجاز است. حداکثر حجم ۲۰ مگابایت است؛ آن را در چند فایل تهیه کنید.';
        },
      };
    }
  </script>
  @endpush
</x-layouts.admin>
