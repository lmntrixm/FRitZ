{{-- Add / edit product (A-ProductEdit, A-FlowEdit): six sections, category fields that follow the category,
     media, relations, internal price, live public card, sticky save bar. --}}
@php
  use App\Http\Controllers\Admin\ProductController as PC;
  use App\Support\Fa;
  use App\Support\ProductAudit;
  $isAdmin = auth()->user()->isAdmin();
  $catId = (int) old('category_id', $p->category_id);
  $protoSel = collect(old('protocols', $p->exists ? $p->protocols->where('is_active', true)->pluck('id')->all() : []))->map('intval')->all();
  $images = $p->exists ? $p->images()->map(fn ($m) => ['id' => $m->id, 'url' => $m->url(), 'name' => $m->original_name, 'removed' => false])->values() : collect();
  $sheet = $p->exists ? $p->datasheet() : null;
  $specs = old('specs', $p->exists ? $p->customSpecs->map(fn ($s) => ['label' => $s->label, 'value' => $s->value])->all() : []);
  $rel = fn ($list) => $list->map(fn ($x) => ['id' => $x->id, 'name' => $x->name, 'code' => $x->model_code])->values();
  $secErr = [
    'base' => $errors->hasAny(['name', 'model_code', 'brand_id', 'category_id', 'country_of_origin', 'protocols']),
    'spec' => collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'f.') || str_starts_with($k, 'specs')),
    'desc' => $errors->hasAny(['description', 'pitch']),
    'media' => collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'images') || $k === 'datasheet'),
    'rel' => false, 'int' => $errors->has('internal_price'),
  ];
  $dot = fn ($k) => $secErr[$k] ? 'er' : ($audit ? ['ok' => 'ok', 'er' => 'er', 'wn' => ''][$audit['sections'][$k]] : '');
  $sections = ['base' => '۱. اطلاعات پایه', 'spec' => '۲. مشخصات فنی', 'desc' => '۳. توضیحات', 'media' => '۴. رسانه', 'rel' => '۵. ارتباط‌ها', 'int' => '۶. اطلاعات داخلی'];
  $catArt = $categories->mapWithKeys(fn ($c) => [$c->id => $c->art ?: 'panel7']);
  $brandNames = $brands->mapWithKeys(fn ($b) => [$b->id => $b->name]);
  $protoNames = $protocols->mapWithKeys(fn ($x) => [$x->id => $x->name]);
  $title = $p->exists ? 'ویرایش محصول' : 'افزودن محصول';
  $init = [
    'name' => old('name', $p->name), 'code' => old('model_code', $p->model_code), 'brand' => (int) old('brand_id', $p->brand_id),
    'cat' => $catId, 'protos' => $protoSel, 'images' => $images, 'specs' => array_values($specs),
    'compatible' => $rel($p->exists ? $p->compatible : collect()), 'related' => $rel($p->exists ? $p->related : collect()),
  ];
@endphp
<x-layouts.admin :title="$title" active="products">
  <x-slot:actions>
    @if ($p->exists)
      <span class="t-c mut3">ساخته‌شده {{ Fa::date($p->created_at, 'd MMMM') }}@if($p->creator) · {{ $p->creator->name }}@endif · آخرین ویرایش {{ Fa::ago($p->updated_at) }}@if($p->updater)، {{ $p->updater->name }}@endif</span>
      <a class="btn btn-g btn-sm" href="{{ route('product', $p) }}" target="_blank"><x-icon i="external" s="16" />{{ $p->status === 'published' ? 'مشاهده در کاتالوگ' : 'پیش‌نمایش در کاتالوگ' }}</a>
    @endif
  </x-slot:actions>

  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:-8px">
    <a class="crumb" href="{{ route('admin.products.index') }}" style="color:var(--acT);font-weight:700"><x-icon i="chevBack" s="14" />همه محصولات</a>
    @if ($p->exists)<span class="code" style="color:var(--tx)">{{ $p->model_code }}</span><span class="st {{ $p->status === 'published' ? 'pub' : 'draft' }}">{{ $p->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}</span>@endif
  </div>

  @if ($errors->any())
    <div class="msgbox er" role="alert"><x-icon i="alert" s="18" /><span><b>{{ Fa::digits($errors->count()) }} مورد ذخیره را متوقف کرد.</b> {{ $errors->first() }}</span></div>
  @elseif ($audit && $audit['blockers'])
    <div class="msgbox er" role="alert"><x-icon i="alert" s="18" /><span><b>{{ Fa::digits(count($audit['blockers'])) }} مورد مانع انتشار است.</b> {{ implode('، ', $audit['blockers']) }} را تکمیل کنید.</span></div>
  @elseif ($audit && $audit['warnings'])
    <div class="msgbox wn"><x-icon i="info" s="18" /><span>{{ implode('. ', $audit['warnings']) }}.</span></div>
  @endif

  <form id="pform" method="post" enctype="multipart/form-data" action="{{ $p->exists ? route('admin.products.update', $p) : route('admin.products.store') }}"
        x-data="productForm(@js($init))"
        @input="dirty++" @change="dirty++" @submit="leaving = true"
        style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start">
    @csrf
    @if ($p->exists) @method('put') @endif

    <div style="flex:999 1 560px;min-width:0;display:flex;flex-direction:column;gap:12px">
      {{-- 1. base --}}
      <details class="sec-card" id="s-base" open>
        <summary class="sec-hd"><span>{{ $sections['base'] }}</span><span style="flex:1"></span><x-icon i="chevDown" s="20" /></summary>
        <div class="sec-bd">
          <div class="frow">
            <div class="fld"><label class="lab" for="name">نام فارسی <span class="req">*</span></label>
              <input id="name" name="name" @class(['inp', 'err' => $errors->has('name')]) x-model="name" required maxlength="190" placeholder="مثلاً تاچ پنل ۷ اینچ MixPad Matter">
              @error('name')<span class="emsg">{{ $message }}</span>@enderror</div>
            <div class="fld"><label class="lab" for="model_code">کد مدل <span class="req">*</span></label>
              <input id="model_code" name="model_code" @class(['inp', 'ltr', 'err' => $errors->has('model_code')]) dir="ltr" x-model="code" required maxlength="64">
              @error('model_code')<span class="emsg">{{ $message }}</span>@else<span class="hint">یکتا؛ برای تطبیق در به‌روزرسانی فایلی استفاده می‌شود.</span>@enderror</div>
          </div>
          <div class="frow">
            <div class="fld"><label class="lab" for="brand_id">برند <span class="req">*</span></label>
              <select id="brand_id" name="brand_id" @class(['inp', 'err' => $errors->has('brand_id')]) x-model.number="brand" required>
                <option value="">انتخاب کنید</option>
                @foreach ($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}{{ $b->is_active ? '' : ' (غیرفعال)' }}</option>@endforeach
              </select>@error('brand_id')<span class="emsg">{{ $message }}</span>@enderror</div>
            <div class="fld"><label class="lab" for="country_of_origin">کشور سازنده</label>
              <input id="country_of_origin" name="country_of_origin" class="inp" value="{{ old('country_of_origin', $p->country_of_origin) }}" maxlength="60" placeholder="مثلاً اسپانیا"></div>
            <div class="fld"><label class="lab" for="category_id">دسته <span class="req">*</span></label>
              <select id="category_id" name="category_id" @class(['inp', 'err' => $errors->has('category_id')]) x-model.number="cat" required>
                <option value="">انتخاب کنید</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
              </select>@error('category_id')<span class="emsg">{{ $message }}</span>@enderror</div>
          </div>
          <div class="fld"><span class="lab">پروتکل <span class="hint">(چندانتخابی؛ برای سرور، آیفون و صوت می‌تواند خالی بماند)</span></span>
            <div class="chips" role="group" aria-label="پروتکل">
              @foreach ($protocols as $x)
                <label class="chip" :class="protos.includes({{ $x->id }}) && 'on'"><input type="checkbox" class="sr" name="protocols[]" value="{{ $x->id }}" x-model.number="protos">
                  <span x-show="protos.includes({{ $x->id }})"><x-icon i="check" s="14" sw="2" /></span><span class="lat">{{ $x->name }}</span></label>
              @endforeach
            </div>
            @if ($inactiveProtocols->isNotEmpty())
              <span class="hint">این محصول پروتکل غیرفعال <span class="lat">{{ $inactiveProtocols->pluck('name')->join('، ') }}</span> هم دارد که در کاتالوگ عمومی نمایش داده نمی‌شود.</span>
            @endif
          </div>
          <label class="swl" style="padding:4px 14px;background:var(--sf2)"><span>نمایش در «منتخب‌ها»ی صفحه اصلی</span>
            <input type="hidden" name="is_featured" value="0"><input type="checkbox" class="sw" name="is_featured" value="1" @checked(old('is_featured', $p->is_featured))></label>
        </div>
      </details>

      {{-- 2. specs: the fields come from the category («دسته‌ها و فیلترها») --}}
      <details class="sec-card" id="s-spec" open>
        <summary class="sec-hd"><span>{{ $sections['spec'] }}</span><span class="t-c mut3" style="font-weight:600" x-text="catName ? 'فیلدهای دسته «' + catName + '»' : ''"></span><span style="flex:1"></span><x-icon i="chevDown" s="20" /></summary>
        <div class="sec-bd">
          <p class="t-s mut" x-show="!cat">اول دسته را انتخاب کنید؛ فیلدهای فنی همان دسته اینجا می‌آید.</p>
          @foreach ($categories as $c)
            @if ($c->fields->isNotEmpty())
              <div class="frow" x-show="cat === {{ $c->id }}" @if($catId !== $c->id) x-cloak @endif>
                @foreach ($c->fields as $fd)
                  @php $name = 'f['.$fd->id.']'; $err = $errors->first('f.'.$fd->id) ?: $errors->first('f.'.$fd->id.'.*'); $val = old('f.'.$fd->id, $p->exists && $p->category_id === $c->id ? PC::fieldValue($p, $fd) : ''); @endphp
                  <div class="fld"><label class="lab" for="f{{ $fd->id }}">{{ $fd->label }} @if($fd->required_to_publish)<span class="req" title="لازم برای انتشار">*</span>@endif</label>
                    @switch($fd->type)
                      @case('select')
                        <select id="f{{ $fd->id }}" name="{{ $name }}" @class(['inp', 'err' => $err]) :disabled="cat !== {{ $c->id }}">
                          <option value="">انتخاب کنید</option>
                          @foreach ($fd->options as $o)<option value="{{ $o->id }}" @selected((string) $val === (string) $o->id)>{{ $o->label }}</option>@endforeach
                        </select>
                        @break
                      @case('multi')
                        <div class="chips">@foreach ($fd->options as $o)<label class="chip" x-data="{ on: {{ in_array((string) $o->id, (array) $val, true) ? 'true' : 'false' }} }" :class="on && 'on'"><input type="checkbox" class="sr" name="{{ $name }}[]" value="{{ $o->id }}" x-model="on" :disabled="cat !== {{ $c->id }}">{{ $o->label }}</label>@endforeach</div>
                        @break
                      @case('number')
                        <div style="display:flex;gap:8px;align-items:center"><input id="f{{ $fd->id }}" name="{{ $name }}" @class(['inp', 'ltr', 'err' => $err]) dir="ltr" inputmode="decimal" value="{{ $val }}" :disabled="cat !== {{ $c->id }}">@if($fd->unit)<span class="t-s mut">{{ $fd->unit }}</span>@endif</div>
                        @break
                      @case('bool')
                        <select id="f{{ $fd->id }}" name="{{ $name }}" class="inp" :disabled="cat !== {{ $c->id }}"><option value="">نامشخص</option><option value="1" @selected($val === '1')>بله</option><option value="0" @selected($val === '0')>خیر</option></select>
                        @break
                      @default
                        <input id="f{{ $fd->id }}" name="{{ $name }}" @class(['inp', 'err' => $err]) value="{{ $val }}" maxlength="190" :disabled="cat !== {{ $c->id }}">
                    @endswitch
                    @if ($err)<span class="emsg"><x-icon i="alert" s="14" />{{ $err }}</span>
                    @elseif ($fd->required_to_publish)<span class="hint">برای انتشار لازم است@if($fd->is_filter)؛ در فیلتر عمومی «{{ $fd->label }}» استفاده می‌شود@endif.</span>@endif
                  </div>
                @endforeach
              </div>
            @endif
          @endforeach
          <div class="msgbox in" x-show="cat"><x-icon i="layers" s="18" /><span>این فیلدها از «دسته‌ها و فیلترها» می‌آیند. با تغییر دسته، فیلدهای دسته جدید نشان داده می‌شوند و مقدارهای دسته قبلی پس از ذخیره حذف می‌شوند.</span></div>

          <div class="fld">
            <span class="lab">مشخصات دلخواه</span>
            <div class="panel" style="background:var(--sf2)" x-show="specs.length">
              <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr) 44px;gap:8px;padding:10px;font-size:12px;font-weight:700;color:var(--tx3)"><span>برچسب</span><span>مقدار</span><span></span></div>
              <template x-for="(s, i) in specs" :key="i">
                <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr) 44px;gap:8px;padding:0 10px 10px;align-items:center">
                  <input class="inp" style="background:var(--bg)" :name="'specs[' + i + '][label]'" x-model="s.label" maxlength="120" placeholder="مثلاً ولتاژ تغذیه" :aria-label="'برچسب ردیف ' + FA(i + 1)">
                  <input class="inp" style="background:var(--bg)" :name="'specs[' + i + '][value]'" x-model="s.value" maxlength="255" placeholder="مثلاً 100–240 VAC" :aria-label="'مقدار ردیف ' + FA(i + 1)">
                  <button type="button" class="ib" aria-label="حذف ردیف" @click="specs.splice(i, 1); dirty++"><x-icon i="trash" s="18" /></button>
                </div>
              </template>
            </div>
            <button type="button" class="btn btn-g btn-sm" style="align-self:flex-start;color:var(--acT)" @click="specs.push({ label: '', value: '' }); dirty++"><x-icon i="plus" s="16" sw="2" />افزودن ردیف</button>
          </div>
        </div>
      </details>

      {{-- 3. description --}}
      <details class="sec-card" id="s-desc" open x-data="{ d: @js(old('description', $p->description) ?? '') }">
        <summary class="sec-hd"><span>{{ $sections['desc'] }}</span><span style="flex:1"></span><x-icon i="chevDown" s="20" /></summary>
        <div class="sec-bd">
          <div class="fld"><label class="lab" for="description">توضیح عمومی محصول</label>
            <textarea id="description" name="description" class="inp" rows="4" maxlength="4000" x-model="d"></textarea>
            <span class="hint" :style="d.length && (d.length < 150 || d.length > 400) ? 'color:var(--wn);font-weight:700' : ''" x-text="FA(d.length) + ' نویسه · پیشنهاد: ۱۵۰ تا ۴۰۰'"></span></div>
          <div class="fld"><label class="lab" for="pitch">دلیل پیشنهاد در «راهنمای انتخاب»</label>
            <input id="pitch" name="pitch" class="inp" value="{{ old('pitch', $p->pitch) }}" maxlength="190" placeholder="مثلاً جایگزین کلید فعلی در همان قوطی ۸۶؛ هر پل یک مدار روشنایی.">
            <span class="hint">یک جمله کوتاه زیر محصول در نتیجه راهنما. خالی باشد، نشان داده نمی‌شود.</span></div>
        </div>
      </details>

      {{-- 4. media --}}
      <details class="sec-card" id="s-media" open>
        <summary class="sec-hd"><span>{{ $sections['media'] }}</span><span class="t-c mut3" style="font-weight:600" x-text="FA(images.filter(m => !m.removed).length + newFiles.length) + ' عکس · ' + ({{ $sheet ? 'true' : 'false' }} || sheetFile ? 'با دیتاشیت' : 'بدون دیتاشیت')"></span><span style="flex:1"></span><x-icon i="chevDown" s="20" /></summary>
        <div class="sec-bd">
          <div style="display:flex;flex-wrap:wrap;gap:10px">
            <template x-for="(m, i) in images" :key="m.id">
              <div class="ph" style="width:120px;height:120px" :style="i === firstKept && 'box-shadow:inset 0 0 0 2px var(--tx)'" :class="m.removed && 'removed'">
                <img :src="m.url" :alt="m.name">
                <input type="hidden" :name="m.removed ? 'remove_media[]' : 'media_order[]'" :value="m.id">
                <span class="st pub" style="position:absolute;bottom:6px;right:6px;height:22px" x-show="i === firstKept">اصلی</span>
                <span class="st er" style="position:absolute;bottom:6px;right:6px;height:22px" x-show="m.removed">حذف با ذخیره</span>
                <span style="position:absolute;top:4px;left:4px;display:flex;gap:2px">
                  <button type="button" class="ib" style="width:32px;height:32px;background:var(--bg)" x-show="!m.removed && i !== firstKept" @click="makeFirst(i)" aria-label="عکس اصلی شود"><x-icon i="arrowUp" s="16" /></button>
                  <button type="button" class="ib" style="width:32px;height:32px;background:var(--bg)" @click="m.removed = !m.removed; dirty++" :aria-label="m.removed ? 'برگرداندن عکس' : 'حذف عکس'"><span x-show="!m.removed"><x-icon i="trash" s="16" /></span><span x-show="m.removed"><x-icon i="refresh" s="16" /></span></button>
                </span>
              </div>
            </template>
            <template x-for="f in newFiles" :key="f.url">
              <div class="ph" style="width:120px;height:120px"><img :src="f.url" alt=""><span class="st draft" style="position:absolute;bottom:6px;right:6px;height:22px">جدید</span></div>
            </template>
            <label class="drop" :class="over && 'over'" style="width:120px;height:120px;padding:10px;gap:6px;justify-content:center;cursor:pointer"
                   @dragover.prevent="over = true" @dragleave="over = false" @drop.prevent="over = false; addFiles($event.dataTransfer.files)">
              <x-icon i="upload" s="22" /><span class="t-c" style="font-weight:700">افزودن عکس</span><span class="t-c mut3" style="font-size:12px">JPG، PNG، WebP</span>
              <input type="file" name="images[]" x-ref="imgs" multiple accept="image/jpeg,image/png,image/webp" class="sr" @change="previewFiles($event.target.files)">
            </label>
          </div>
          <span class="hint">عکس‌ها به WebP با حداکثر ۱۶۰۰ پیکسل تبدیل می‌شوند. اولین عکس، عکس اصلی کارت است. تا وقتی عکسی نباشد، کاتالوگ طرح جای‌نگهدار دسته را نشان می‌دهد.</span>
          @error('images.*')<span class="emsg"><x-icon i="alert" s="14" />{{ $message }}</span>@enderror

          <div class="fld"><span class="lab">دیتاشیت <span class="lat">PDF</span></span>
            @if ($sheet)
              <div class="file"><x-icon i="doc" s="20" /><span style="flex:1;display:flex;flex-direction:column"><a class="lat" style="font-size:13px;font-weight:700" href="{{ $sheet->url() }}" target="_blank">{{ $sheet->original_name }}</a><span class="t-c mut3">{{ Fa::digits(number_format($sheet->bytes / 1048576, 1)) }} مگابایت</span></span></div>
            @endif
            <label class="drop" style="padding:18px;flex-direction:row;justify-content:center;cursor:pointer">
              <x-icon i="doc" s="20" /><span class="t-s" style="font-weight:700" x-text="sheetFile || '{{ $sheet ? 'جایگزینی دیتاشیت' : 'آپلود دیتاشیت' }} PDF'"></span><span class="t-c mut3">تا ۲۰ مگابایت</span>
              <input type="file" name="datasheet" accept="application/pdf" class="sr" @change="sheetFile = $event.target.files[0]?.name || ''">
            </label>
            @error('datasheet')<span class="emsg"><x-icon i="alert" s="14" />{{ $message }}</span>@enderror
          </div>
        </div>
      </details>

      {{-- 5. relations --}}
      <details class="sec-card" id="s-rel" @if($p->exists && ($p->compatible->isNotEmpty() || $p->related->isNotEmpty())) open @endif>
        <summary class="sec-hd"><span>{{ $sections['rel'] }}</span><span class="t-c mut3" style="font-weight:600" x-text="FA(compatible.length + related.length) + ' محصول'"></span><span style="flex:1"></span><x-icon i="chevDown" s="20" /></summary>
        <div class="sec-bd">
          @foreach (['compatible' => ['سازگار با', 'در صفحه محصول زیر «سازگار با» می‌آید.'], 'related' => ['محصولات مرتبط', 'اول این‌ها و بعد بقیه محصولات همان دسته نشان داده می‌شوند.']] as $key => [$lab, $hint])
            <div class="fld" x-data="picker('{{ $key }}')">
              <span class="lab">{{ $lab }}</span>
              <div class="chips" x-show="$data[key].length">
                <template x-for="(x, i) in $data[key]" :key="x.id">
                  <span class="chip rm"><input type="hidden" name="{{ $key }}[]" :value="x.id"><span x-text="x.name"></span> <span class="code" x-text="x.code"></span><button type="button" class="x" :aria-label="'حذف ' + x.name" @click="$data[key].splice(i, 1); dirty++"><x-icon i="x" s="14" /></button></span>
                </template>
              </div>
              <div style="position:relative">
                <div class="field" style="height:44px;background:var(--sf2)"><x-icon i="search" s="18" /><input x-model="q" @input.debounce.200ms="find()" @keydown.escape="res = []" placeholder="جست‌وجوی نام یا کد محصول" aria-label="جست‌وجوی {{ $lab }}" autocomplete="off"></div>
                <div class="card" role="listbox" x-show="res.length" x-cloak @click.outside="res = []" style="position:absolute;top:50px;inset-inline:0;z-index:3;box-shadow:var(--sh2);padding:6px 0;background:var(--bg)">
                  <template x-for="r in res" :key="r.id">
                    <button type="button" class="kbd-row" role="option" style="width:100%;border:0;background:none;cursor:pointer;text-align:right" @click="add(r)">
                      <span class="thumb" style="width:32px;height:32px"><svg class="art" width="28" height="28" viewBox="0 0 200 200" aria-hidden="true"><use :href="'#art-' + r.art"/></svg></span>
                      <span style="flex:1" x-text="r.name"></span><span class="code" x-text="r.code"></span>
                    </button>
                  </template>
                </div>
              </div>
              <span class="hint">{{ $hint }}</span>
            </div>
          @endforeach
        </div>
      </details>

      {{-- 6. internal --}}
      <details class="sec-card" id="s-int" @if($isAdmin) open @endif>
        <summary class="sec-hd"><span>{{ $sections['int'] }}</span><span class="int"><x-icon i="lock" s="12" sw="2" />فقط داخلی، عمومی نمی‌شود</span><span style="flex:1"></span><x-icon i="chevDown" s="20" /></summary>
        <div class="sec-bd"><div class="frow">
          <div class="fld"><label class="lab" for="internal_price">قیمت (تومان)</label>
            @if ($isAdmin)
              <input id="internal_price" name="internal_price" @class(['inp', 'ltr', 'err' => $errors->has('internal_price')]) dir="ltr" inputmode="numeric" value="{{ old('internal_price', $p->internal_price) }}">
              @error('internal_price')<span class="emsg">{{ $message }}</span>@enderror
            @else
              <input id="internal_price" class="inp ltr dis" dir="ltr" value="{{ $p->internal_price ? number_format($p->internal_price) : '—' }}" disabled>
            @endif
          </div>
        </div><span class="hint">این فیلد در صفحه‌ها، جست‌وجو و خروجی عمومی کاتالوگ وجود ندارد{{ $isAdmin ? '' : ' و برای ویرایشگر محتوا فقط‌خواندنی است' }}.</span></div>
      </details>
    </div>

    <aside style="flex:0 1 300px;min-width:260px;display:flex;flex-direction:column;gap:16px;position:sticky;top:16px">
      <div class="panel" style="padding:10px">
        <p class="glabel" style="padding:4px 10px 6px">بخش‌ها</p>
        <nav class="qnav" style="position:static" aria-label="ناوبری فرم">
          @foreach ($sections as $k => $l)
            <a href="#s-{{ $k }}" @click="document.getElementById('s-{{ $k }}').open = true"><span class="dot {{ $dot($k) }}"></span>{{ $l }}
              @if ($secErr[$k] || ($audit && $audit['sections'][$k] === 'er'))<span class="t-c" style="margin-inline-start:auto;color:var(--err);font-weight:800">{{ $secErr[$k] ? 'خطا' : 'لازم' }}</span>
              @elseif ($audit && $audit['sections'][$k] === 'wn')<span class="t-c mut3" style="margin-inline-start:auto">ناقص</span>@endif
            </a>
          @endforeach
        </nav>
        @if ($audit)
          <div style="padding:10px 10px 4px;border-top:1px solid var(--line);margin-top:8px">
            <div class="cmpl" style="justify-content:space-between"><span class="t-c mut" style="font-weight:700">تکمیل اطلاعات</span><span>{{ Fa::digits($audit['pct']) }}٪</span></div>
            <div class="prog" style="margin-top:8px"><i style="width:{{ $audit['pct'] }}%"></i></div>
            @if ($audit['missing'])<p class="t-c mut3" style="margin-top:6px">بدون {{ implode('، ', $audit['missing']) }}</p>@endif
          </div>
        @endif
      </div>
      <div class="panel" style="padding:14px;display:flex;flex-direction:column;gap:10px">
        <div style="display:flex;justify-content:space-between;align-items:center"><span class="glabel">پیش‌نمایش زنده کارت عمومی</span><span class="pb" style="direction:rtl">بدون قیمت</span></div>
        <article class="card pc" aria-hidden="true">
          <div class="ph">
            <template x-if="firstImage"><img :src="firstImage" alt=""></template>
            <template x-if="!firstImage"><svg class="art" width="150" height="150" viewBox="0 0 200 200"><use :href="'#art-' + (arts[cat] || 'panel7')"/></svg></template>
          </div>
          <div class="pc-b"><span class="brand" x-text="brands[brand] || 'برند'"></span><h3 class="pc-n" x-text="name || 'نام محصول'"></h3>
            <div class="pc-meta"><span class="code" x-text="code || 'MODEL-CODE'"></span><span class="pbs"><template x-for="id in protos" :key="id"><span class="pb" x-text="pnames[id]"></span></template></span></div></div>
          <div class="pc-f"><span class="pc-add"><x-icon i="plus" s="16" sw="2" />افزودن به استعلام</span></div>
        </article>
      </div>
    </aside>

    <div class="savebar" style="flex-basis:100%;margin:0 -32px -64px">
      <span class="sb-hide" style="display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--wn)" x-show="dirty" x-cloak><span style="width:8px;height:8px;border-radius:50%;background:currentColor"></span>تغییرات ذخیره‌نشده</span>
      <div style="flex:1"></div>
      <a class="btn btn-g btn-sm sb-hide" href="{{ route('admin.products.index') }}">انصراف</a>
      @if ($p->exists)
        <button class="btn btn-g btn-sm sb-sec" type="submit" form="dup"><x-icon i="copy" s="16" /><span class="lbl">کپی محصول</span></button>
        <button class="btn btn-xs btn-sm sb-sec" type="submit" form="del"><x-icon i="trash" s="16" /><span class="lbl">حذف</span></button>
      @endif
      @if ($p->status === 'published')
        <button class="btn btn-s btn-sm sb-sec" type="submit" name="intent" value="draft" data-confirm='{"title":"برگرداندن به پیش‌نویس؟","body":"محصول از کاتالوگ عمومی، جست‌وجو و مقایسه برداشته می‌شود تا دوباره منتشر شود. تغییرات این فرم هم ذخیره می‌شود.","ok":"برگرداندن به پیش‌نویس"}'><x-icon i="eye" s="16" /><span class="lbl">برگرداندن به پیش‌نویس</span></button>
        <button class="btn btn-p btn-sm" type="submit" name="intent" value="save">ذخیره تغییرات</button>
      @else
        <button class="btn btn-s btn-sm" type="submit" name="intent" value="draft">ذخیره پیش‌نویس</button>
        <button class="btn btn-p btn-sm" type="submit" name="intent" value="publish"><x-icon i="globe" s="16" />انتشار</button>
      @endif
    </div>
  </form>

  @if ($p->exists)
    <form id="dup" method="post" action="{{ route('admin.products.duplicate', $p) }}" data-confirm='{"title":"کپی از این محصول ساخته شود؟","body":"یک پیش‌نویس با همه مشخصات، عکس‌ها و دیتاشیت ساخته می‌شود و کد مدلش «-COPY» می‌گیرد. تغییرات ذخیره‌نشده این فرم در کپی نمی‌آید.","ok":"ساخت کپی"}'>@csrf</form>
    <form id="del" method="post" action="{{ route('admin.products.destroy', $p) }}" data-confirm='{{ json_encode(['title' => 'حذف «'.$p->name.'»؟', 'body' => 'از کاتالوگ عمومی، جدول مقایسه و لیست‌های «سازگار با» برداشته می‌شود. استعلام‌های قبلی حفظ می‌شوند. تا ۳۰ روز از «حذف‌شده‌ها» قابل بازگردانی است.', 'ok' => 'بله، حذف شود', 'danger' => true], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) }}'>@csrf @method('delete')</form>
  @endif

  @push('head')
  <script>
    function productForm(init) {
      return {
        ...init, dirty: 0, leaving: false, newFiles: [], sheetFile: '', over: false,
        arts: @json($catArt), brands: @json($brandNames), pnames: @json($protoNames), cats: @json($categories->pluck('name', 'id')),
        init() {
          this.$nextTick(() => { this.dirty = 0; });
          window.addEventListener('beforeunload', (e) => { if (this.dirty && !this.leaving) { e.preventDefault(); e.returnValue = ''; } });
        },
        get catName() { return this.cats[this.cat] || ''; },
        get firstKept() { return this.images.findIndex((m) => !m.removed); },
        get firstImage() { const m = this.images.find((x) => !x.removed); return m ? m.url : (this.newFiles[0] ? this.newFiles[0].url : ''); },
        makeFirst(i) { const [m] = this.images.splice(i, 1); this.images.unshift(m); this.dirty++; },
        previewFiles(files) { this.newFiles = [...files].map((f) => ({ url: URL.createObjectURL(f) })); },
        addFiles(files) {
          const dt = new DataTransfer();
          [...files].filter((f) => /^image\/(jpeg|png|webp)$/.test(f.type)).forEach((f) => dt.items.add(f));
          this.$refs.imgs.files = dt.files; this.previewFiles(dt.files); this.dirty++;
        },
      };
    }
    function picker(key) {
      return {
        key, q: '', res: [],
        async find() {
          const t = this.q.trim(); if (!t) { this.res = []; return; }
          try {
            const r = await fetch(@json(route('admin.products.lookup')) + '?except={{ $p->id ?? 0 }}&q=' + encodeURIComponent(t), { headers: { Accept: 'application/json' } });
            const have = this.$data[key].map((x) => x.id);
            this.res = (await r.json()).filter((x) => !have.includes(x.id));
          } catch (e) { this.res = []; }
        },
        add(r) { this.$data[key].push({ id: r.id, name: r.name, code: r.code }); this.q = ''; this.res = []; this.dirty++; },
      };
    }
  </script>
  @endpush
</x-layouts.admin>
