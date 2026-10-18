{{-- Product list (A-Products): search and filters, «فقط ناقص‌ها», completeness, bulk actions, pagination, states. --}}
@php
  use App\Support\Fa;
  use App\Support\ProductAudit;
  $qs = fn (array $over) => route('admin.products.index', array_filter(array_merge($f, ['page' => null], $over), fn ($v) => $v !== '' && $v !== null && $v !== false && $v !== 0));
  $label = fn ($list, $id) => optional($list->firstWhere('id', $id))->name;
  $filtered = $f['q'] !== '' || $f['brand'] || $f['category'] || $f['protocol'] || $f['status'] || $f['incomplete'];
  $trash = $f['status'] === 'trashed';
@endphp
<x-layouts.admin title="محصولات" active="products">
  <x-slot:actions>
    <span class="t-s mut3" style="font-weight:600">{{ Fa::digits($counts['all']) }} محصول · {{ Fa::digits($counts['published']) }} منتشرشده · {{ Fa::digits($counts['draft']) }} پیش‌نویس</span>
    <a class="btn btn-p btn-sm" href="{{ route('admin.products.create', array_filter(['category' => $f['category'] ?: null])) }}"><x-icon i="plus" s="18" sw="2" />افزودن محصول</a>
  </x-slot:actions>

  <div class="panel" x-data="{ sel: [], all: @js($rows->pluck('p.id')->values()) }">
    <form class="toolbar" method="get" action="{{ route('admin.products.index') }}" role="search">
      <div class="field" style="flex:1 1 220px;max-width:300px;height:40px"><x-icon i="search" s="18" /><input name="q" value="{{ $f['q'] }}" placeholder="نام یا کد مدل" aria-label="جست‌وجو در محصولات"></div>
      @foreach ([['brand', 'برند', $brands], ['category', 'دسته', $categories], ['protocol', 'پروتکل', $protocols]] as [$key, $title, $list])
        <label class="selbtn @if($f[$key]) on @endif" style="position:relative">
          <span>{{ $f[$key] ? $title.': '.$label($list, $f[$key]) : $title }}</span><x-icon i="chevDown" s="14" />
          <select name="{{ $key }}" onchange="this.form.submit()" aria-label="{{ $title }}" style="position:absolute;inset:0;opacity:0;cursor:pointer">
            <option value="">همه</option>
            @foreach ($list as $o)<option value="{{ $o->id }}" @selected($f[$key] === $o->id)>{{ $o->name }}{{ isset($o->is_active) && ! $o->is_active ? ' (غیرفعال)' : '' }}</option>@endforeach
          </select>
        </label>
      @endforeach
      <label class="selbtn @if($f['status']) on @endif" style="position:relative">
        <span>{{ ['' => 'وضعیت', 'published' => 'منتشرشده', 'draft' => 'پیش‌نویس', 'trashed' => 'حذف‌شده‌ها'][$f['status']] }}</span><x-icon i="chevDown" s="14" />
        <select name="status" onchange="this.form.submit()" aria-label="وضعیت" style="position:absolute;inset:0;opacity:0;cursor:pointer">
          <option value="">همه</option><option value="published" @selected($f['status'] === 'published')>منتشرشده</option><option value="draft" @selected($f['status'] === 'draft')>پیش‌نویس</option>
          <option value="trashed" @selected($trash)>حذف‌شده‌ها ({{ Fa::digits($counts['trashed']) }})</option>
        </select>
      </label>
      <a class="selbtn @if($f['incomplete']) on @endif" href="{{ $qs(['incomplete' => ! $f['incomplete']]) }}" aria-pressed="{{ $f['incomplete'] ? 'true' : 'false' }}"><x-icon i="alert" s="16" />فقط ناقص‌ها</a>
      @if ($filtered)<a class="btn btn-g btn-sm" href="{{ route('admin.products.index') }}" style="color:var(--acT)">برداشتن فیلترها</a>@endif
      <div style="flex:1"></div>
      <label class="selbtn" style="position:relative"><x-icon i="sort" s="16" /><span>{{ ['updated' => 'آخرین ویرایش', 'least' => 'کمترین تکمیل', 'name' => 'نام'][$f['sort']] }}</span>
        <select name="sort" onchange="this.form.submit()" aria-label="مرتب‌سازی" style="position:absolute;inset:0;opacity:0;cursor:pointer">
          <option value="updated" @selected($f['sort'] === 'updated')>آخرین ویرایش</option><option value="least" @selected($f['sort'] === 'least')>کمترین تکمیل</option><option value="name" @selected($f['sort'] === 'name')>نام</option>
        </select>
      </label>
      @if ($f['incomplete'])<input type="hidden" name="incomplete" value="1">@endif
      <noscript><button class="btn btn-s btn-sm">اعمال</button></noscript>
    </form>

    {{-- bulk bar --}}
    <form method="post" action="{{ route('admin.products.bulk') }}" x-show="sel.length" x-cloak style="padding:10px 14px 0" id="bulk"
          data-confirm='{"dynamic":"bulkConfirm"}'>
      @csrf
      <template x-for="id in sel" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
      <div class="bulk" role="region" aria-label="عملیات گروهی">
        <b style="font-size:13px" x-text="FA(sel.length) + ' مورد انتخاب شده'"></b>
        @if ($trash)
          <button class="btn btn-g btn-sm" name="action" value="restore"><x-icon i="refresh" s="16" />بازگردانی</button>
        @else
          <button class="btn btn-g btn-sm" name="action" value="publish"><x-icon i="globe" s="16" />انتشار</button>
          <button class="btn btn-g btn-sm" name="action" value="draft"><x-icon i="eye" s="16" />پیش‌نویس</button>
          <div style="flex:1"></div>
          <button class="btn btn-x btn-sm" name="action" value="delete"><x-icon i="trash" s="16" />حذف</button>
        @endif
        <button class="btn btn-g btn-sm" type="button" @click="sel = []">لغو انتخاب</button>
      </div>
    </form>

    @if ($rows->total() === 0)
      @if (! $filtered)
        <div class="empty" style="padding:56px 24px"><span class="ico"><x-icon i="box" s="30" /></span><h3 class="t-h3">هنوز محصولی ثبت نشده است</h3>
          <p class="t-s mut">اولین محصول را دستی بسازید. ورود یکجا از فایل در مرحله به‌روزرسانی اضافه می‌شود.</p>
          <a class="btn btn-p btn-sm" href="{{ route('admin.products.create') }}"><x-icon i="plus" s="16" sw="2" />افزودن محصول</a></div>
      @else
        <div class="empty" style="padding:56px 24px"><span class="ico"><x-icon i="noResult" s="30" /></span>
          <h3 class="t-h3">@if($f['q'] !== '')محصولی با «<bdi>{{ $f['q'] }}</bdi>» پیدا نشد@else محصولی با این فیلترها نیست @endif</h3>
          <p class="t-s mut">فیلترها را بردارید تا در همه محصولات جست‌وجو شود.</p>
          <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center">
            <a class="btn btn-s btn-sm" href="{{ route('admin.products.index', array_filter(['q' => $f['q']])) }}">برداشتن فیلترها</a>
            @if ($f['q'] !== '' && preg_match('/^[\x21-\x7E]+$/', $f['q']))<a class="btn btn-g btn-sm" style="color:var(--acT)" href="{{ route('admin.products.create', ['code' => $f['q']]) }}">ساخت محصول با این کد</a>@endif
          </div></div>
      @endif
    @else
      <div class="tw"><table class="tbl">
        <thead><tr>
          <th style="width:36px"><input type="checkbox" class="cbx" aria-label="انتخاب همه" :checked="sel.length && sel.length === all.length" @change="sel = $event.target.checked ? [...all] : []"></th>
          <th>محصول</th><th>کد مدل</th><th>برند</th><th>دسته</th><th>پروتکل</th><th>وضعیت</th><th>تکمیل اطلاعات</th><th>آخرین ویرایش</th><th><span class="sr">عملیات</span></th>
        </tr></thead>
        <tbody>
          @foreach ($rows as $r)
            @php $p = $r['p']; $a = $r['a']; $img = $p->images()->first(); $protos = $p->protocols; @endphp
            <tr :class="sel.includes({{ $p->id }}) && 'sel'">
              <td><input type="checkbox" class="cbx" value="{{ $p->id }}" x-model.number="sel" aria-label="انتخاب {{ $p->name }}"></td>
              <td><a class="pname" @unless($trash) href="{{ route('admin.products.edit', $p) }}" @endunless>
                @if ($img)<span class="thumb"><img src="{{ $img->url() }}" alt="" style="width:100%;height:100%;object-fit:contain"></span>
                @else<span class="thumb miss" title="بدون عکس"><x-icon i="image" s="16" /><span class="sr">بدون عکس</span></span>@endif
                <b>{{ $p->name }}</b></a></td>
              <td><span class="code" style="color:var(--tx)">{{ $p->model_code }}</span></td>
              <td><span class="lat" style="font-weight:600">{{ $p->brand?->name }}</span></td>
              <td>{{ $p->category?->name }}</td>
              <td>
                @forelse ($protos as $x)
                  <span class="pb" @if(! $x->is_active) style="text-decoration:line-through" title="غیرفعال؛ در کاتالوگ عمومی نیست" @endif>{{ $x->name }}</span>
                @empty
                  <span class="t-c" style="color:var(--wn);font-weight:700">تعیین نشده</span>
                @endforelse
              </td>
              <td>@if ($trash)<span class="st er">حذف‌شده</span>@else<span class="st {{ $p->status === 'published' ? 'pub' : 'draft' }}">{{ $p->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}</span>@endif</td>
              <td><span class="cmpl"><span class="bar"><i style="width:{{ $a['pct'] }}%;background:{{ ProductAudit::color($a['pct']) }}"></i></span>{{ Fa::digits($a['pct']) }}٪</span>
                @if ($a['missing'])<span class="t-c mut3" style="display:block">بدون {{ implode('، ', array_slice($a['missing'], 0, 2)) }}</span>@endif</td>
              <td class="t-c mut">{{ $trash ? 'حذف '.Fa::ago($p->deleted_at) : Fa::ago($p->updated_at) }}@if(! $trash && $p->updater)<br>{{ $p->updater->name }}@endif</td>
              <td><span style="display:inline-flex">
                @if ($trash)
                  <form method="post" action="{{ route('admin.products.bulk') }}">@csrf<input type="hidden" name="ids[]" value="{{ $p->id }}"><button class="btn btn-g btn-sm" name="action" value="restore"><x-icon i="refresh" s="16" />بازگردانی</button></form>
                @else
                  <a class="ib" style="width:36px;height:36px" href="{{ route('admin.products.edit', $p) }}" aria-label="ویرایش {{ $p->name }}"><x-icon i="edit" s="18" /></a>
                  <a class="ib" style="width:36px;height:36px" href="{{ route('product', $p) }}" target="_blank" aria-label="مشاهده {{ $p->name }} در کاتالوگ"><x-icon i="external" s="18" /></a>
                @endif
              </span></td>
            </tr>
          @endforeach
        </tbody>
      </table></div>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-top:1px solid var(--line);flex-wrap:wrap;gap:8px">
        <span class="t-c mut">نمایش {{ Fa::digits($rows->firstItem()) }} تا {{ Fa::digits($rows->lastItem()) }} از {{ Fa::digits($rows->total()) }} محصول</span>
        @if ($rows->hasPages())
          <nav class="pg" aria-label="صفحه‌بندی">
            @if ($rows->onFirstPage())<button disabled aria-label="صفحه قبل"><x-icon i="chevBack" s="16" /></button>@else<a class="ib" style="width:38px;height:38px" href="{{ $rows->previousPageUrl() }}" aria-label="صفحه قبل"><x-icon i="chevBack" s="16" /></a>@endif
            @foreach ($rows->getUrlRange(1, $rows->lastPage()) as $n => $url)
              <a href="{{ $url }}" @class(['on' => $n === $rows->currentPage()]) style="min-width:38px;height:38px;border-radius:50%;display:inline-grid;place-items:center;font-weight:600;font-size:14px;{{ $n === $rows->currentPage() ? 'background:var(--tx);color:var(--bg)' : '' }}" @if($n === $rows->currentPage()) aria-current="page" @endif>{{ Fa::digits($n) }}</a>
            @endforeach
            @if ($rows->hasMorePages())<a class="ib" style="width:38px;height:38px" href="{{ $rows->nextPageUrl() }}" aria-label="صفحه بعد"><x-icon i="chevNext" s="16" /></a>@else<button disabled aria-label="صفحه بعد"><x-icon i="chevNext" s="16" /></button>@endif
          </nav>
        @endif
      </div>
    @endif
  </div>

  @push('head')
  <script>
    /* Bulk confirm copy: says exactly what each action will do. Restore needs no confirmation. */
    function bulkConfirm(form, btn) {
      const n = FA(form.querySelectorAll('input[name="ids[]"]').length);
      return {
        delete: { title: 'حذف ' + n + ' محصول؟', body: 'این محصولات از کاتالوگ عمومی، جدول مقایسه و لیست‌های «سازگار با» برداشته می‌شوند. استعلام‌های قبلی حفظ می‌شوند. تا ۳۰ روز از «حذف‌شده‌ها» قابل بازگردانی است.', ok: 'بله، حذف شود', danger: true },
        draft: { title: n + ' محصول به پیش‌نویس برگردد؟', body: 'این محصولات از کاتالوگ عمومی برداشته می‌شوند تا دوباره منتشر شوند. داده‌هایشان حفظ می‌شود.', ok: 'برگرداندن به پیش‌نویس' },
        publish: { title: 'انتشار ' + n + ' محصول؟', body: 'این محصولات در کاتالوگ عمومی دیده می‌شوند. محصولی که فیلد لازمش خالی باشد منتشر نمی‌شود و نامش گزارش می‌شود.', ok: 'بله، منتشر شود' },
        restore: { title: 'بازگردانی ' + n + ' محصول؟', body: 'با همان وضعیت قبلی (منتشرشده یا پیش‌نویس) برمی‌گردند.', ok: 'بازگردانی' },
      }[btn ? btn.value : 'delete'];
    }
  </script>
  @endpush
</x-layouts.admin>
