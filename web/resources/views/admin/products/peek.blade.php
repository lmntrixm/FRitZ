{{-- Side peek (fetched into the products list): what the row is, what it still needs, status in place. --}}
@php
  use App\Support\Fa;
  use App\Support\ProductAudit;
  use App\Support\ProductSheet;
  $sheet = (new ProductSheet)->render($p);
  $specs = collect($sheet)->filter(fn ($v, $k) => str_starts_with($k, ProductSheet::SPEC))
    ->mapWithKeys(fn ($v, $k) => [mb_substr($k, mb_strlen(ProductSheet::SPEC)) => $v]);
@endphp
<div class="pk-h">
  <span class="t-c mut3" style="flex:1">{{ $p->category?->name ?? 'بدون دسته' }}</span>
  <a class="btn btn-s btn-sm" href="{{ route('admin.products.edit', $p) }}"><x-icon i="edit" s="16" />ویرایش کامل</a>
  @if ($p->status === 'published')<a class="ib sm" href="{{ route('product', $p) }}" target="_blank" aria-label="مشاهده در کاتالوگ عمومی" title="مشاهده در کاتالوگ عمومی"><x-icon i="external" s="18" /></a>@endif
  <button class="ib sm" type="button" @click="close()" aria-label="بستن"><x-icon i="x" s="18" /></button>
</div>
<div class="pk-b">
  <div class="pk-img"><x-pimg :p="$p" s="160" /></div>
  <div>
    <h2 class="pk-t">{{ $p->name }}</h2>
    <p class="pk-m"><span class="code" style="color:var(--tx)">{{ $p->model_code }}</span> · <span class="lat">{{ $p->brand?->name ?? 'بدون برند' }}</span>@if ($p->country_of_origin) · {{ $p->country_of_origin }}@endif</p>
  </div>

  <dl class="pk-props">
    <dt>وضعیت</dt><dd><x-status :p="$p" /></dd>
    <dt>پروتکل</dt><dd>@forelse ($p->protocols as $x)<span class="pb" @if(! $x->is_active) style="text-decoration:line-through" title="غیرفعال؛ در کاتالوگ عمومی نیست" @endif>{{ $x->name }}</span>@empty<span style="color:var(--wn);font-weight:700">تعیین نشده</span>@endforelse</dd>
    <dt>منتخب</dt><dd>{{ $p->is_featured ? 'بله، در خانه کاتالوگ' : 'خیر' }}</dd>
    @if ($p->internal_price !== null)<dt>قیمت</dt><dd><span class="num">{{ Fa::digits(number_format((int) $p->internal_price)) }}</span> <span class="int" style="height:20px">فقط داخلی، عمومی نمی‌شود</span></dd>@endif
    <dt>آخرین ویرایش</dt><dd>{{ Fa::ago($p->updated_at) }}{{ $p->updater ? ' · ' . $p->updater->name : '' }}</dd>
  </dl>

  <section class="pk-s">
    <h3>تکمیل اطلاعات <span class="cmpl" style="margin-inline-start:auto"><span class="bar"><i style="width:{{ $a['pct'] }}%;background:{{ ProductAudit::color($a['pct']) }}"></i></span>{{ Fa::digits($a['pct']) }}٪</span></h3>
    @if ($a['blockers'])
      <div class="msgbox er"><x-icon i="lock" s="16" /><span><b>منتشر نمی‌شود تا:</b> {{ implode('، ', $a['blockers']) }}</span></div>
    @endif
    @if ($a['missing'])
      <ul class="pk-miss">@foreach ($a['missing'] as $m)<li><x-icon i="dot" s="14" />{{ $m }}</li>@endforeach</ul>
    @else
      <p class="t-s" style="color:var(--okT);font-weight:700">همه بخش‌ها کامل است.</p>
    @endif
  </section>

  @if ($specs->filter()->isNotEmpty())
    <section class="pk-s">
      <h3>مشخصات فنی</h3>
      <table class="spec" style="font-size:13px"><tbody>
        @foreach ($specs as $k => $v)<tr><th>{{ $k }}</th><td>{{ $v !== '' ? $v : '—' }}</td></tr>@endforeach
      </tbody></table>
    </section>
  @endif

  @if ($p->description)
    <section class="pk-s"><h3>توضیحات</h3><p class="t-s" style="color:var(--tx2);white-space:pre-line">{{ \Illuminate\Support\Str::limit($p->description, 360) }}</p></section>
  @endif
</div>
