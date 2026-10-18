{{-- Update review (A-Update steps 2–5, A-FlowUpdate): validation, change preview, options, apply; then the result and rollback. --}}
@php
  use App\Support\Fa;
  $d = $u->diff;
  $c = $d['counts'];
  $report = $d['report'] ?? null;
  $preview = $u->status === 'preview';
  $short = fn ($s) => $s === '' ? '—' : (mb_strlen($s) > 90 ? mb_substr($s, 0, 90) . '…' : $s);
  $rows = [];
  foreach ($d['items'] as $it) {
    $base = ['n' => (string) $it['name'], 'c' => $it['code'], 'r' => $it['row']];
    if ($it['kind'] === 'mod') {
      foreach ($it['changes'] as $ch) {
        $rows[] = $base + ['k' => 'mod', 'f' => $ch['f'], 'b' => $short($ch['b']), 'a' => $short($ch['a']), 'int' => $ch['f'] === 'قیمت داخلی'];
      }
    } else {
      $sum = collect($it['changes'])->whereIn('f', ['برند', 'دسته', 'وضعیت'])->pluck('a')->filter()->join(' · ');
      $rows[] = $base + ['k' => $it['kind'], 'f' => $it['kind'] === 'new' ? Fa::digits(count($it['changes'])) . ' فیلد' : '', 'b' => '', 'a' => $sum, 'int' => false];
    }
  }
  foreach ($d['absent'] as $a) {
    $rows[] = ['k' => 'del', 'n' => (string) $a['name'], 'c' => $a['code'], 'r' => null, 'f' => '', 'b' => '', 'a' => '', 'int' => false];
  }
  $cards = [
    'mod' => ['~', 'ویرایش‌شده', $c['mod'], Fa::digits($c['mod']) . ' محصول · ' . Fa::digits(collect($d['items'])->where('kind', 'mod')->sum(fn ($i) => count($i['changes']))) . ' فیلد'],
    'new' => ['+', 'جدید', $c['new'], Fa::digits($c['new']) . ' محصول'],
    'same' => ['=', 'بدون تغییر', $c['same'], Fa::digits($c['same']) . ' محصول'],
    'del' => ['−', $preview ? 'حذف‌شونده (اگر فعال شود)' : 'غایب در فایل', $c['absent'], Fa::digits($c['absent']) . ' محصول'],
  ];
  $dmClass = ['mod' => 'mod', 'new' => 'add', 'same' => 'same', 'del' => 'del'];
  $first = collect(['mod', 'new', 'del', 'same'])->first(fn ($k) => $cards[$k][2] > 0) ?? 'mod';
  $errs = collect($d['messages'])->where('level', 'error');
  $warns = collect($d['messages'])->where('level', 'warning');
  $rowsText = fn ($r) => $r ? ' · ردیف ' . Fa::digits(implode('، ', array_slice($r, 0, 8))) . (count($r) > 8 ? ' و ' . Fa::digits(count($r) - 8) . ' ردیف دیگر' : '') : '';
  $hasPrice = in_array('price', $d['present'], true) && collect($d['items'])->contains(fn ($i) => collect($i['changes'])->contains('f', 'قیمت داخلی'));
  $nothing = $c['new'] + $c['mod'] === 0;
  $state = ['applied' => ['st pub', 'اعمال شد'], 'failed' => ['st er', 'ناموفق'], 'rolled_back' => ['st draft', 'بازگردانی‌شده'], 'preview' => ['st wn', 'منتظر تأیید']][$u->status] ?? ['st draft', $u->status];
@endphp
<x-layouts.admin title="به‌روزرسانی محصولات" active="update">
  <x-slot:actions>
    <span class="file" style="padding:6px 12px"><x-icon :i="$u->kind === 'fritz' ? 'lock' : 'table'" s="16" /><span class="code" style="color:var(--tx)">{{ $u->file_name }}</span></span>
    <a class="btn btn-g btn-sm" href="{{ route('admin.update.index', ['tab' => $preview ? $u->kind : 'history']) }}">{{ $preview ? 'فایل دیگر' : 'تاریخچه' }}</a>
  </x-slot:actions>

  <div x-data="updateReview(@js($rows), @js($first), {{ $preview ? 3 : 6 }}, @js(old('mode', $errors->has('delete_ack') ? 'delete' : 'keep')))" style="display:flex;flex-direction:column;gap:16px">
    <ol class="stepper" style="list-style:none;margin:0;padding:0" aria-label="مراحل">
      @foreach (['آپلود', 'اعتبارسنجی', 'پیش‌نمایش تغییرات', 'گزینه‌ها', 'تأیید و اعمال'] as $i => $s)
        <li class="step" :class="{{ $i + 1 }} < step ? 'done' : ({{ $i + 1 }} === step ? 'cur' : '')" :aria-current="{{ $i + 1 }} === step ? 'step' : null">
          <span class="n"><span x-show="{{ $i + 1 }} < step"><x-icon i="check" s="14" sw="2.4" /></span><span x-show="{{ $i + 1 }} >= step">{{ Fa::digits($i + 1) }}</span></span>{{ $s }}</li>
      @endforeach
    </ol>

    {{-- result --}}
    @if ($u->status === 'applied')
      <section class="panel"><div class="panel-h"><h2>نتیجه</h2><span class="{{ $state[0] }}">{{ $state[1] }}</span></div><div class="panel-b" style="display:flex;flex-direction:column;gap:12px">
        <div class="msgbox ok"><x-icon i="checkCircle" s="18" /><span><b>به‌روزرسانی اعمال شد.</b> {{ Fa::digits($report['added'] ?? 0) }} جدید · {{ Fa::digits($report['modified'] ?? 0) }} ویرایش · {{ Fa::digits($report['deleted'] ?? 0) }} حذف · {{ Fa::digits($c['skipped']) }} ردیف رد شد{{ ! empty($report['ignore_price']) ? ' · قیمت‌ها نادیده گرفته شد' : '' }}.
          <span class="mut">{{ Fa::date($u->applied_at, 'd MMMM y') }}، ساعت {{ Fa::digits($u->applied_at->format('H:i')) }} · {{ $u->uploader?->name }}</span></span></div>
        @if (! empty($report['drafted']))
          <div class="msgbox wn" style="flex-direction:column;gap:6px;align-items:stretch"><span><b>{{ Fa::digits(count($report['drafted'])) }} محصول به‌جای انتشار، پیش‌نویس ماند</b> چون فیلد الزامی دسته‌اش خالی است:</span>
            <ul style="margin:0;padding-inline-start:18px">@foreach (array_slice($report['drafted'], 0, 20) as $x)<li><bdi>{{ $x }}</bdi></li>@endforeach</ul>
            @if (count($report['drafted']) > 20)<span>و {{ Fa::digits(count($report['drafted']) - 20) }} محصول دیگر.</span>@endif</div>
        @endif
        <span class="t-c mut3">پشتیبان <span class="lat">{{ pathinfo((string) $u->backup_path, PATHINFO_FILENAME) }}</span> پیش از اعمال ساخته شد.</span>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn btn-s btn-sm" href="{{ route('admin.update.file', $u) }}"><x-icon i="download" s="16" />دانلود فایل اصلی</a>
          @if ($canRollback)
            <form method="post" action="{{ route('admin.update.rollback', $u) }}" data-confirm='{{ json_encode(['title' => 'بازگردانی این به‌روزرسانی؟', 'body' => 'کاتالوگ به وضعیت پیش از این فایل برمی‌گردد: ' . Fa::digits($report['added'] ?? 0) . ' محصول جدید پاک می‌شود، ' . Fa::digits($report['modified'] ?? 0) . ' محصول به مقدار قبلی و ' . Fa::digits($report['deleted'] ?? 0) . ' محصول حذف‌شده به کاتالوگ برمی‌گردد. ویرایش‌هایی که از آن زمان روی همین محصولات در پنل انجام شده هم برمی‌گردد.', 'ok' => 'بله، بازگردانی شود', 'danger' => true], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) }}'>
              @csrf<button class="btn btn-xs btn-sm" type="submit"><x-icon i="history" s="16" />بازگردانی به نسخه قبل</button></form>
          @else
            <span class="hint" style="align-self:center">بازگردانی فقط برای آخرین به‌روزرسانی اعمال‌شده ممکن است.</span>
          @endif
        </div>
      </div></section>
    @elseif ($u->status === 'failed')
      <section class="panel"><div class="panel-h"><h2>نتیجه</h2><span class="{{ $state[0] }}">{{ $state[1] }}</span></div><div class="panel-b" style="display:flex;flex-direction:column;gap:12px">
        <div class="msgbox er"><x-icon i="alert" s="18" /><span><b>اعمال تغییرات متوقف شد و هیچ تغییری اعمال نشد.</b> کاتالوگ همان است که پیش از این فایل بود. متن خطا: <bdi>{{ $u->error }}</bdi></span></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-p btn-sm" href="{{ route('admin.update.index', ['tab' => $u->kind]) }}"><x-icon i="refresh" s="16" />تلاش دوباره</a>
          <a class="btn btn-s btn-sm" href="{{ route('admin.update.file', $u) }}"><x-icon i="download" s="16" />دانلود فایل اصلی</a></div>
      </div></section>
    @elseif ($u->status === 'rolled_back')
      <section class="panel"><div class="panel-h"><h2>نتیجه</h2><span class="{{ $state[0] }}">{{ $state[1] }}</span></div><div class="panel-b">
        <div class="msgbox in"><x-icon i="history" s="18" /><span><b>این به‌روزرسانی بازگردانی شد.</b> {{ Fa::date($u->rolled_back_at, 'd MMMM y') }}، ساعت {{ Fa::digits($u->rolled_back_at->format('H:i')) }}. کاتالوگ به وضعیت پیش از این فایل برگشت.</span></div>
      </div></section>
    @endif

    {{-- step 2: validation --}}
    <section class="panel"><div class="panel-h"><h2>اعتبارسنجی</h2><span @class(['st', 'pub' => $errs->isEmpty(), 'wn' => $errs->isNotEmpty()])>{{ $errs->isEmpty() ? 'معتبر' : Fa::digits($c['skipped']) . ' ردیف رد می‌شود' }}</span></div>
      <div class="panel-b" style="display:flex;flex-direction:column;gap:10px">
        <div @class(['msgbox', 'ok' => $errs->isEmpty(), 'wn' => $errs->isNotEmpty()])><x-icon :i="$errs->isEmpty() ? 'checkCircle' : 'alert'" s="18" />
          <span><b>{{ $u->kind === 'fritz' ? 'بسته رمزگشایی شد و امضای آن معتبر است.' : 'فایل خوانده شد.' }}</b> {{ Fa::digits($c['new'] + $c['mod'] + $c['same']) }} محصول در فایل{{ $c['skipped'] ? '، ' . Fa::digits($c['skipped']) . ' ردیف به‌خاطر خطا کنار گذاشته می‌شود' : '' }}.</span></div>
        @if ($errs->isNotEmpty() || $warns->isNotEmpty())
          <div style="display:flex;flex-direction:column;gap:6px;font-size:13px">
            @foreach ($errs->concat($warns)->take(40) as $m)
              <span style="display:flex;gap:8px;align-items:baseline"><span @class(['st', 'er' => $m['level'] === 'error', 'wn' => $m['level'] !== 'error']) style="flex:none">{{ $m['level'] === 'error' ? 'خطا' : 'هشدار' }}</span><span>{{ $m['text'] }}<span class="mut3">{{ $rowsText($m['rows']) }}</span></span></span>
            @endforeach
            @if ($errs->count() + $warns->count() > 40)<span class="mut3">و {{ Fa::digits($errs->count() + $warns->count() - 40) }} مورد دیگر.</span>@endif
          </div>
        @endif
      </div></section>

    {{-- step 3: change preview --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(180px,100%),1fr));gap:12px" role="group" aria-label="نوع تغییر">
      @foreach ($cards as $k => [$sym, $label, $n])
        <button type="button" class="dcard" :class="k === '{{ $k }}' && 'on'" :aria-pressed="k === '{{ $k }}'" @click="k = '{{ $k }}'; page = 0">
          <span style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--tx2)"><span class="dm {{ $dmClass[$k] }}">{{ $sym }}</span>{{ $label }}</span><span class="v">{{ Fa::digits($n) }}</span></button>
      @endforeach
    </div>
    <section class="panel">
      <div class="panel-h" style="flex-wrap:wrap;gap:8px">
        @foreach ($cards as $k => [$sym, $label, $n, $sub])
          <h2 x-show="k === '{{ $k }}'" @if($k !== $first) x-cloak @endif>{{ $label }} <span class="t-c mut3" style="font-weight:600">{{ $sub }}</span></h2>
        @endforeach
        <div class="field" style="height:38px;max-width:260px"><x-icon i="search" s="16" /><input placeholder="جست‌وجوی کد یا نام" aria-label="جست‌وجوی کد یا نام" x-model.debounce.200ms="q" @input="page = 0"></div>
      </div>
      <div class="tw"><table class="tbl"><thead><tr><th style="width:40px">نوع</th><th>محصول</th><th x-text="k === 'new' ? 'فیلدها' : 'فیلد'">فیلد</th><th x-show="k === 'mod'">قبل</th><th x-show="k === 'mod'" style="width:24px"></th><th x-text="k === 'mod' ? 'بعد' : (k === 'new' ? 'خلاصه' : '')">بعد</th><th style="width:70px">ردیف</th></tr></thead><tbody>
        <template x-for="(r, i) in shown" :key="i">
          <tr :class="'r-' + r.k">
            <td><span class="dm" :class="{ mod: 'mod', new: 'add', same: 'same', del: 'del' }[r.k]" x-text="{ mod: '~', new: '+', same: '=', del: '−' }[r.k]"></span></td>
            <td><b style="font-size:13px" x-text="r.n"></b> <span class="code" x-text="r.c"></span></td>
            <td class="t-c mut" style="font-weight:700"><span x-text="r.f"></span><span x-show="r.int" class="int" style="height:20px;margin-inline-start:6px">فقط داخلی</span></td>
            <td x-show="k === 'mod'"><span class="was" x-text="r.b"></span></td>
            <td x-show="k === 'mod'" style="color:var(--tx3)"><x-icon i="arrowNext" s="14" /></td>
            <td><span :class="k === 'mod' && 'now'" x-text="r.a"></span></td>
            <td class="t-c mut3" x-text="r.r ? FA(r.r) : '—'"></td>
          </tr>
        </template>
        <tr x-show="!shown.length"><td colspan="7" class="t-s mut" style="text-align:center;padding:28px" x-text="q ? 'موردی با این جست‌وجو پیدا نشد.' : 'موردی در این دسته نیست.'"></td></tr>
      </tbody></table></div>
      <div style="padding:12px 14px;border-top:1px solid var(--line);display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px" x-show="filtered.length > per">
        <span class="t-c mut" x-text="'نمایش ' + FA(shown.length) + ' از ' + FA(filtered.length)"></span>
        <div class="pg"><template x-for="p in pages" :key="p"><button type="button" :class="p === page && 'on'" :aria-current="p === page ? 'page' : null" @click="page = p" x-text="FA(p + 1)"></button></template></div>
      </div>
    </section>

    @if ($preview)
      @error('apply')<div class="msgbox er" role="alert"><x-icon i="alert" s="18" /><span>{{ $message }}</span></div>@enderror
      <div style="display:flex;gap:8px;flex-wrap:wrap" x-show="step === 3">
        <button type="button" class="btn btn-p" @click="go(4)">ادامه: گزینه‌ها<x-icon i="arrowNext" s="18" /></button>
        <form method="post" action="{{ route('admin.update.discard', $u) }}" data-confirm='{"title":"این فایل کنار گذاشته شود؟","body":"بررسی این فایل پاک می‌شود و هیچ تغییری در کاتالوگ اعمال نمی‌شود.","ok":"کنار بگذار"}'>@csrf @method('delete')<button class="btn btn-g" type="submit">کنار گذاشتن فایل</button></form>
      </div>

      <form id="apply" method="post" action="{{ route('admin.update.apply', $u) }}" data-confirm='{"dynamic":"applyConfirm"}' @submit="busy = true" x-show="step >= 4" x-cloak
            data-new="{{ $c['new'] }}" data-mod="{{ $c['mod'] }}" data-absent="{{ $c['absent'] }}">
        @csrf
        <div class="g2a" style="align-items:start">
          {{-- step 4: options --}}
          <section class="panel" x-ref="opts"><div class="panel-h"><h2>گزینه‌ها</h2><span class="st wn" x-show="mode === 'delete'" x-cloak>نیاز به تأیید</span></div><div class="panel-b" style="display:flex;flex-direction:column;gap:10px">
            <label class="opt" style="padding:10px 12px" :style="mode === 'keep' ? 'background:var(--sf2)' : ''"><input type="radio" class="rdo" name="mode" value="keep" x-model="mode">فقط افزودن و ویرایش<span class="n">محصولاتی که در فایل نیستند دست نمی‌خورند</span></label>
            <label @class(['opt', 'dis' => $c['absent'] === 0]) style="padding:10px 12px" :style="mode === 'delete' ? 'box-shadow:inset 0 0 0 1.5px var(--err)' : ''">
              <input type="radio" class="rdo" name="mode" value="delete" x-model="mode" @disabled($c['absent'] === 0) :disabled="{{ $c['absent'] === 0 ? 'true' : 'false' }}">همچنین حذف محصولات غایب<span class="n">{{ $c['absent'] ? Fa::digits($c['absent']) . ' محصول' : 'محصول غایبی نیست' }}</span></label>
            <input type="hidden" name="delete_absent" :value="mode === 'delete' ? 1 : 0">
            <div class="msgbox er" style="flex-direction:column;gap:6px;align-items:stretch" x-show="mode === 'delete'" x-cloak>
              <span><b>{{ Fa::digits($c['absent']) }} محصول حذف می‌شوند</b> و از کاتالوگ، مقایسه و «سازگار با» برداشته می‌شوند. تا ۳۰ روز در «حذف‌شده‌ها» می‌مانند و با بازگردانی این به‌روزرسانی برمی‌گردند.</span>
              <label class="opt" style="color:var(--tx);min-height:36px"><input type="checkbox" class="cbx" name="delete_ack" value="1" x-model="ack">می‌دانم {{ Fa::digits($c['absent']) }} محصول حذف می‌شود</label>
              @error('delete_ack')<span class="emsg">{{ $message }}</span>@enderror
            </div>
            @if ($hasPrice)
              <label class="swl" style="padding:4px 12px;background:var(--sf2)"><span>نادیده گرفتن قیمت<br><span class="hint">ستون «قیمت داخلی» فایل اعمال نشود. قیمت فقط داخلی است و عمومی نمی‌شود.</span></span><input type="checkbox" class="sw" name="ignore_price" value="1" x-model="ignorePrice"></label>
            @endif
            <div style="display:flex;gap:8px;flex-wrap:wrap" x-show="step === 4">
              <button type="button" class="btn btn-p btn-sm" @click="go(5)" :disabled="mode === 'delete' && !ack" :class="mode === 'delete' && !ack && 'is-dis'">ادامه: تأیید نهایی</button>
              <button type="button" class="btn btn-g btn-sm" @click="go(3)">مرحله قبل</button>
            </div>
          </section>

          {{-- step 5: final review --}}
          <section class="panel" x-show="step === 5" x-cloak x-ref="final"><div class="panel-h"><h2>تأیید و اعمال</h2><span class="st draft">مرحله آخر</span></div><div class="panel-b" style="display:flex;flex-direction:column;gap:12px">
            <table class="spec" style="font-size:14px"><tbody>
              <tr><th>محصول جدید</th><td>{{ Fa::digits($c['new']) }}</td></tr>
              <tr><th>ویرایش</th><td>{{ Fa::digits($c['mod']) }} محصول</td></tr>
              <tr><th>حذف</th><td x-text="mode === 'delete' ? FA({{ $c['absent'] }}) + ' محصول' : 'هیچ'"></td></tr>
              <tr><th>ردیف ردشده</th><td>{{ Fa::digits($c['skipped']) }}</td></tr>
              @if ($hasPrice)<tr><th>قیمت داخلی</th><td x-text="ignorePrice ? 'نادیده گرفته می‌شود' : 'اعمال می‌شود'"></td></tr>@endif
            </tbody></table>
            <div class="msgbox wn" x-show="!busy"><x-icon i="history" s="16" /><span>پیش از اعمال، نسخه پشتیبان ساخته می‌شود. اگر نتیجه درست نبود، آخرین به‌روزرسانی را می‌توانید بازگردانید.</span></div>
            <div class="msgbox wn" x-show="busy" x-cloak><x-icon i="lock" s="16" /><span><b>این عملیات قابل لغو نیست.</b> تا پایان، صفحه را نبندید.</span></div>
            <div class="prog lg" x-show="busy" x-cloak><i style="width:100%" class="indet"></i></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button class="btn btn-p" type="submit" :disabled="busy" @disabled($nothing && $c['absent'] === 0) @class(['is-dis' => $nothing && $c['absent'] === 0])><x-icon i="check" s="18" /><span x-text="busy ? 'در حال اعمال…' : 'اعمال به‌روزرسانی'">اعمال به‌روزرسانی</span></button>
              <button type="button" class="btn btn-g" @click="go(4)" x-show="!busy">مرحله قبل</button>
            </div>
            @if ($nothing)<span class="hint">این فایل محصول جدید یا ویرایشی ندارد.</span>@endif
          </div></section>
        </div>
      </form>
    @endif
  </div>

  @push('head')
  <script>
    function updateReview(rows, first, step, mode) {
      return {
        rows, k: first, q: '', page: 0, per: 50, step: mode === 'delete' && step === 3 ? 4 : step, mode, ack: false, ignorePrice: false, busy: false,
        get filtered() {
          const q = FZnorm(this.q);
          return this.rows.filter((r) => r.k === this.k && (!q || FZnorm(r.n + ' ' + r.c).includes(q)));
        },
        get pages() { return [...Array(Math.ceil(this.filtered.length / this.per)).keys()]; },
        get shown() { return this.filtered.slice(this.page * this.per, (this.page + 1) * this.per); },
        go(n) {
          this.step = n;
          this.$nextTick(() => { const el = n === 5 ? this.$refs.final : this.$refs.opts; if (el && n > 3) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
        },
      };
    }
    function FZnorm(s) { return String(s || '').toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/[\s\-_./]+/g, ''); }
    function applyConfirm(form) {
      const n = +form.dataset.new, m = +form.dataset.mod, a = +form.dataset.absent;
      const del = form.querySelector('[name=delete_absent]').value === '1';
      const parts = [FA(n) + ' محصول جدید ساخته', FA(m) + ' محصول ویرایش'];
      if (del) parts.push(FA(a) + ' محصول حذف');
      return {
        title: 'اعمال به‌روزرسانی روی کاتالوگ؟',
        body: parts.join('، ') + ' می‌شود و بلافاصله در کاتالوگ عمومی دیده می‌شود. پیش از اعمال نسخه پشتیبان ساخته می‌شود و تا به‌روزرسانی بعدی قابل بازگردانی است. اعمال پس از شروع قابل لغو نیست.',
        ok: 'بله، اعمال شود', danger: del,
      };
    }
  </script>
  @endpush
</x-layouts.admin>
