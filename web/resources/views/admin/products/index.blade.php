{{-- Products: one list, three ways to read it (grouped list, table, board), a side peek for any row,
     status changed in place. Filters, bulk actions and «فقط ناقص‌ها» work in every view. --}}
@php
  use App\Support\Fa;
  use App\Support\ProductAudit;
  $qs = fn (array $over) => route('admin.products.index', array_filter(array_merge($f, ['page' => null], $over), fn ($v) => $v !== '' && $v !== null && $v !== false && $v !== 0 && $v !== 'list' && $v !== 'status' && $v !== 'updated'));
  $label = fn ($list, $id) => optional($list->firstWhere('id', $id))->name;
  $filtered = $f['q'] !== '' || $f['brand'] || $f['category'] || $f['protocol'] || $f['status'] || $f['incomplete'];
  $trash = $f['status'] === 'trashed';
  $title = $current?->name ?? ($trash ? 'حذف‌شده‌ها' : 'محصولات');
  $ids = $f['view'] === 'table' ? $rows->pluck('p.id') : $groups->flatMap(fn ($g) => $g['rows']->pluck('p.id'));
  $showCat = ! $f['category'] && $f['group'] !== 'category';
  $cols = 8 + ($showCat ? 1 : 0);
  $views = ['list' => ['لیست', 'menu'], 'table' => ['جدول', 'table'], 'board' => ['بُرد', 'grid2']];
@endphp
<x-layouts.admin :title="$title" active="products" :crumbs="$current || $trash ? [['محصولات', route('admin.products.index')]] : null">
  <x-slot:actions>
    <span class="t-s mut3" style="font-weight:600">{{ Fa::digits($counts['all']) }} محصول · {{ Fa::digits($counts['published']) }} منتشرشده · {{ Fa::digits($counts['draft']) }} پیش‌نویس</span>
    <a class="btn btn-p btn-sm" href="{{ route('admin.products.create', array_filter(['category' => $f['category'] ?: null])) }}"><x-icon i="plus" s="18" sw="2" />افزودن محصول</a>
  </x-slot:actions>

  <div class="pv" x-data="productsPage(@js($ids->values()))">
    <div class="vbar">
      <nav class="vtabs" aria-label="نما">
        @foreach ($views as $k => [$l, $ic])
          @continue($k === 'board' && $trash)
          <a @class(['vtab', 'on' => $f['view'] === $k]) href="{{ $qs(['view' => $k]) }}" @if($f['view'] === $k) aria-current="page" @endif><x-icon :i="$ic" s="16" />{{ $l }}</a>
        @endforeach
      </nav>
      @if ($f['view'] !== 'table' && ! $trash)
        <label class="selbtn ghost" style="position:relative"><x-icon i="layers" s="16" /><span>گروه: {{ ['status' => 'وضعیت', 'category' => 'دسته', 'brand' => 'برند'][$f['group']] }}</span><x-icon i="chevDown" s="14" />
          <select aria-label="گروه‌بندی" onchange="location.href=this.value" style="position:absolute;inset:0;opacity:0;cursor:pointer">
            @foreach (['status' => 'وضعیت', 'category' => 'دسته', 'brand' => 'برند'] as $gk => $gl)<option value="{{ $qs(['group' => $gk]) }}" @selected($f['group'] === $gk)>{{ $gl }}</option>@endforeach
          </select>
        </label>
      @endif
    </div>

    <form class="toolbar" method="get" action="{{ route('admin.products.index') }}" role="search">
      @if ($f['view'] !== 'list')<input type="hidden" name="view" value="{{ $f['view'] }}">@endif
      @if ($f['group'] !== 'status')<input type="hidden" name="group" value="{{ $f['group'] }}">@endif
      <div class="field" style="flex:1 1 220px;max-width:300px"><x-icon i="search" s="16" /><input name="q" value="{{ $f['q'] }}" placeholder="نام یا کد مدل" aria-label="جست‌وجو در محصولات"></div>
      @foreach ([['brand', 'برند', $brands], ['category', 'دسته', $categories], ['protocol', 'پروتکل', $protocols]] as [$key, $ttl, $list])
        <label class="selbtn @if($f[$key]) on @endif" style="position:relative">
          <span>{{ $f[$key] ? $ttl.': '.$label($list, $f[$key]) : $ttl }}</span><x-icon i="chevDown" s="14" />
          <select name="{{ $key }}" onchange="this.form.submit()" aria-label="{{ $ttl }}" style="position:absolute;inset:0;opacity:0;cursor:pointer">
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
      @if ($filtered)<a class="btn btn-g btn-sm" href="{{ route('admin.products.index', array_filter(['view' => $f['view'] !== 'list' ? $f['view'] : null])) }}" style="color:var(--acT)">برداشتن فیلترها</a>@endif
      <div style="flex:1"></div>
      <label class="selbtn ghost" style="position:relative"><x-icon i="sort" s="16" /><span>{{ ['updated' => 'آخرین ویرایش', 'least' => 'کمترین تکمیل', 'name' => 'نام'][$f['sort']] }}</span>
        <select name="sort" onchange="this.form.submit()" aria-label="مرتب‌سازی" style="position:absolute;inset:0;opacity:0;cursor:pointer">
          <option value="updated" @selected($f['sort'] === 'updated')>آخرین ویرایش</option><option value="least" @selected($f['sort'] === 'least')>کمترین تکمیل</option><option value="name" @selected($f['sort'] === 'name')>نام</option>
        </select>
      </label>
      @if ($f['incomplete'])<input type="hidden" name="incomplete" value="1">@endif
      <noscript><button class="btn btn-s btn-sm">اعمال</button></noscript>
    </form>

    {{-- bulk bar --}}
    <form method="post" action="{{ route('admin.products.bulk') }}" x-show="sel.length" x-cloak id="bulk" class="bulkf" data-confirm='{"dynamic":"bulkConfirm"}'>
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

    @if ($total === 0)
      <div class="panel">
      @if (! $filtered)
        <div class="empty" style="padding:56px 24px"><span class="ico"><x-icon i="box" s="30" /></span><h3 class="t-h3">هنوز محصولی ثبت نشده است</h3>
          <p class="t-s mut">اولین محصول را دستی بسازید، یا همه را یکجا از «به‌روزرسانی ← ورود از اکسل» وارد کنید.</p>
          <a class="btn btn-p btn-sm" href="{{ route('admin.products.create') }}"><x-icon i="plus" s="16" sw="2" />افزودن محصول</a></div>
      @else
        <div class="empty" style="padding:56px 24px"><span class="ico"><x-icon i="noResult" s="30" /></span>
          <h3 class="t-h3">@if($f['q'] !== '')محصولی با «<bdi>{{ $f['q'] }}</bdi>» پیدا نشد@elseif($current && ! $f['brand'] && ! $f['protocol'] && ! $f['status'] && ! $f['incomplete'])«{{ $current->name }}» هنوز محصولی ندارد@else محصولی با این فیلترها نیست @endif</h3>
          <p class="t-s mut">{{ $current ? 'اولین محصول این دسته را بسازید، یا فیلترها را بردارید.' : 'فیلترها را بردارید تا در همه محصولات جست‌وجو شود.' }}</p>
          <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center">
            @if ($current)<a class="btn btn-p btn-sm" href="{{ route('admin.products.create', ['category' => $current->id]) }}"><x-icon i="plus" s="16" sw="2" />محصول در «{{ $current->name }}»</a>@endif
            <a class="btn btn-s btn-sm" href="{{ route('admin.products.index', array_filter(['q' => $f['q']])) }}">برداشتن فیلترها</a>
            @if ($f['q'] !== '' && preg_match('/^[\x21-\x7E]+$/', $f['q']))<a class="btn btn-g btn-sm" style="color:var(--acT)" href="{{ route('admin.products.create', ['code' => $f['q']]) }}">ساخت محصول با این کد</a>@endif
          </div></div>
      @endif
      </div>

    @elseif ($f['view'] === 'board')
      <div class="board" x-data="board()">
        @foreach ($groups as $g)
          <section class="bcol" data-col="{{ $g['key'] }}" aria-label="{{ $g['label'] }}"
                   x-data="{ n: {{ $g['rows']->count() }} }" @fz-recount.window="n = $el.querySelectorAll('.bcard').length"
                   @if ($f['group'] === 'status') @dragover.prevent="over = '{{ $g['key'] }}'" @dragleave.self="over = null" @drop.prevent="drop('{{ $g['key'] }}')" :class="over === '{{ $g['key'] }}' && 'over'" @endif>
            <header class="bch">
              @if ($g['chip'])<span class="st {{ $g['chip'] }}">{{ $g['label'] }}</span>@else<span class="bgi"><x-icon :i="$g['icon']" s="16" /></span><b>{{ $g['label'] }}</b>@endif
              <span class="tn" x-text="FA(n)">{{ Fa::digits($g['rows']->count()) }}</span>
              <a class="ib sm" style="margin-inline-start:auto" href="{{ route('admin.products.create', array_filter(['category' => str_starts_with($g['key'], 'category-') ? (int) substr($g['key'], 9) : ($f['category'] ?: null)])) }}" aria-label="افزودن محصول به «{{ $g['label'] }}»" title="افزودن محصول"><x-icon i="plus" s="16" /></a>
            </header>
            <div class="bcards">
              @foreach ($g['rows'] as $r)
                @php $p = $r['p']; $a = $r['a']; @endphp
                <article class="bcard" id="card-{{ $p->id }}" @if ($f['group'] === 'status') draggable="true" @dragstart="start($event, { id: {{ $p->id }}, url: @js(route('admin.products.status', $p)), name: @js($p->name), status: $el.querySelector('.stm .st').classList.contains('pub') ? 'published' : 'draft' })" @dragend="over = null" @endif>
                  <div class="bimg"><x-pimg :p="$p" s="96" /></div>
                  <a class="bname" href="{{ route('admin.products.edit', $p) }}" @click="if (!$event.ctrlKey && !$event.metaKey) { $event.preventDefault(); show(@js(route('admin.products.peek', $p)), $el) }">{{ $p->name }}</a>
                  <span class="bmeta"><span class="code">{{ $p->model_code }}</span> · <span class="lat">{{ $p->brand?->name }}</span></span>
                  @if ($p->protocols->isNotEmpty())<span class="bpro">@foreach ($p->protocols as $x)<span class="pb" @if(! $x->is_active) style="text-decoration:line-through" @endif>{{ $x->name }}</span>@endforeach</span>@endif
                  <footer><x-status :p="$p" />
                    <span class="cmpl" title="تکمیل اطلاعات: {{ Fa::digits($a['pct']) }}٪"><span class="bar"><i style="width:{{ $a['pct'] }}%;background:{{ ProductAudit::color($a['pct']) }}"></i></span>{{ Fa::digits($a['pct']) }}٪</span></footer>
                </article>
              @endforeach
              @if ($g['rows']->isEmpty())<p class="bnone">کارتی را اینجا بکشید.</p>@endif
            </div>
          </section>
        @endforeach
      </div>
      @if ($f['group'] === 'status')<p class="hint">کارت را بین ستون‌ها بکشید تا وضعیتش عوض شود. با صفحه‌کلید، از منوی وضعیت روی هر کارت استفاده کنید.</p>@endif

    @else
      {{-- list (grouped) and table share the row --}}
      <div class="panel lv">
        <div class="tw"><table class="tbl">
          <thead><tr>
            <th style="width:36px"><input type="checkbox" class="cbx" aria-label="انتخاب همه" :checked="sel.length && sel.length === all.length" @change="sel = $event.target.checked ? [...all] : []"></th>
            <th>محصول</th><th class="hm">کد مدل</th><th class="hm">برند</th>@if ($showCat)<th class="hm">دسته</th>@endif<th class="hm">پروتکل</th><th>وضعیت</th><th class="hm">تکمیل</th><th class="hm">آخرین ویرایش</th>
          </tr></thead>
          @foreach ($f['view'] === 'table' ? [['key' => 'all', 'label' => null, 'chip' => null, 'icon' => null, 'rows' => $rows->getCollection()]] : $groups as $g)
            <tbody x-data="{ o: true }">
              @if ($g['label'])
                <tr class="grp"><th colspan="{{ $cols }}" scope="rowgroup">
                  <span class="grph">
                    <button type="button" class="grpt" @click="o = !o" :aria-expanded="o"><x-icon i="chevDown" s="14" sw="2" />
                      @if ($g['chip'])<span class="st {{ $g['chip'] }}">{{ $g['label'] }}</span>@else<span class="bgi"><x-icon :i="$g['icon']" s="16" /></span><b>{{ $g['label'] }}</b>@endif
                      <span class="tn">{{ Fa::digits($g['rows']->count()) }}</span></button>
                    @unless ($trash)<a class="grpadd" href="{{ route('admin.products.create', array_filter(['category' => str_starts_with($g['key'], 'category-') ? (int) substr($g['key'], 9) : ($f['category'] ?: null)])) }}"><x-icon i="plus" s="14" />افزودن</a>@endunless
                  </span></th></tr>
              @endif
              @foreach ($g['rows'] as $r)
                @php $p = $r['p']; $a = $r['a']; $img = $p->images()->first(); @endphp
                <tr x-show="o" :class="sel.includes({{ $p->id }}) && 'sel'">
                  <td><input type="checkbox" class="cbx" value="{{ $p->id }}" x-model.number="sel" aria-label="انتخاب {{ $p->name }}"></td>
                  <td><a class="pname" @unless($trash) href="{{ route('admin.products.edit', $p) }}" @click="if (!$event.ctrlKey && !$event.metaKey) { $event.preventDefault(); show(@js(route('admin.products.peek', $p)), $el) }" @endunless>
                    @if ($img)<span class="thumb"><img src="{{ $img->url() }}" alt="" style="width:100%;height:100%;object-fit:contain"></span>
                    @else<span class="thumb art"><x-art :kind="$p->art()" s="30" aria-hidden="true" /></span>@endif
                    <span class="pnt"><b>{{ $p->name }}</b><span class="code mo">{{ $p->model_code }}</span></span></a></td>
                  <td class="hm"><span class="code" style="color:var(--tx)">{{ $p->model_code }}</span></td>
                  <td class="hm"><span class="lat" style="font-weight:600">{{ $p->brand?->name }}</span></td>
                  @if ($showCat)<td class="hm t-s">{{ $p->category?->name }}</td>@endif
                  <td class="hm">@forelse ($p->protocols as $x)<span class="pb" @if(! $x->is_active) style="text-decoration:line-through" title="غیرفعال؛ در کاتالوگ عمومی نیست" @endif>{{ $x->name }}</span>@empty<span class="t-c" style="color:var(--wn);font-weight:700">تعیین نشده</span>@endforelse</td>
                  <td><x-status :p="$p" :trash="$trash" /></td>
                  <td class="hm"><span class="cmpl" title="{{ $a['missing'] ? 'بدون ' . implode('، ', $a['missing']) : 'کامل' }}"><span class="bar"><i style="width:{{ $a['pct'] }}%;background:{{ ProductAudit::color($a['pct']) }}"></i></span>{{ Fa::digits($a['pct']) }}٪</span></td>
                  <td @class(["t-c", "mut", "hm" => ! $trash]) style="white-space:nowrap">@if ($trash)
                      <form method="post" action="{{ route('admin.products.bulk') }}" style="display:inline">@csrf<input type="hidden" name="ids[]" value="{{ $p->id }}"><button class="btn btn-g btn-sm" name="action" value="restore"><x-icon i="refresh" s="16" />بازگردانی</button></form>
                    @else{{ Fa::ago($p->updated_at) }}@endif</td>
                </tr>
              @endforeach
              @if ($g['label'] && $g['rows']->isEmpty())<tr x-show="o"><td colspan="{{ $cols }}" class="t-c mut3" style="text-align:center">موردی در این گروه نیست.</td></tr>@endif
            </tbody>
          @endforeach
        </table></div>
        @if ($f['view'] === 'table')
          <div class="lvf">
            <span class="t-c mut">نمایش {{ Fa::digits($rows->firstItem()) }} تا {{ Fa::digits($rows->lastItem()) }} از {{ Fa::digits($rows->total()) }} محصول</span>
            @if ($rows->hasPages())
              <nav class="pg" aria-label="صفحه‌بندی">
                @if ($rows->onFirstPage())<button disabled aria-label="صفحه قبل"><x-icon i="chevBack" s="16" /></button>@else<a class="ib sm" href="{{ $rows->previousPageUrl() }}" aria-label="صفحه قبل"><x-icon i="chevBack" s="16" /></a>@endif
                @foreach ($rows->getUrlRange(1, $rows->lastPage()) as $n => $url)
                  <a href="{{ $url }}" @class(['pgn', 'on' => $n === $rows->currentPage()]) @if($n === $rows->currentPage()) aria-current="page" @endif>{{ Fa::digits($n) }}</a>
                @endforeach
                @if ($rows->hasMorePages())<a class="ib sm" href="{{ $rows->nextPageUrl() }}" aria-label="صفحه بعد"><x-icon i="chevNext" s="16" /></a>@else<button disabled aria-label="صفحه بعد"><x-icon i="chevNext" s="16" /></button>@endif
              </nav>
            @endif
          </div>
        @else
          <div class="lvf"><span class="t-c mut">{{ Fa::digits($total) }} محصول</span><span class="t-c mut3">روی نام هر محصول بزنید تا کنار لیست باز شود. Ctrl+کلیک فرم کامل را باز می‌کند.</span></div>
        @endif
      </div>
    @endif

    {{-- side peek --}}
    <div class="pk-wrap" x-show="open" x-cloak @keydown.escape.window="open && !$store.confirm.open && close()">
      <div class="scrim" @click="close()"></div>
      <aside class="pk" role="dialog" aria-modal="true" aria-label="جزئیات محصول" tabindex="-1" x-ref="pk" x-trap="open" x-html="html"
             x-transition:enter="pk-in" x-transition:enter-start="pk-from" x-transition:enter-end="pk-to"></aside>
    </div>
  </div>

  @push('head')
  <script>
    function productsPage(all) { return { ...peek(), sel: [], all }; }
    /* Bulk confirm copy: says exactly what each action will do. */
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
