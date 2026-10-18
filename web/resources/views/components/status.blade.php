{{-- A product's status chip that is also its menu: change it in place, confirmed when it leaves the catalog. --}}
@props(['p', 'trash' => false])
@if ($trash)
  <span class="st er">حذف‌شده</span>
@else
  <div class="stm" x-data="statusChip(@js($p->status), @js(route('admin.products.status', $p)), @js($p->name))" @click.outside="o = false" @keydown.escape.stop="o = false" @fz-moved="status = $event.detail">
    <button type="button" class="st" :class="status === 'published' ? 'pub' : 'draft'" @click="o = !o" :aria-expanded="o" aria-haspopup="menu" :aria-busy="busy"
            aria-label="وضعیت {{ $p->name }}">
      <span x-text="label">{{ $p->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}</span><x-icon i="chevDown" s="12" sw="2.2" />
    </button>
    <div class="menu" role="menu" x-show="o" x-cloak x-transition.opacity.duration.150ms>
      <button type="button" role="menuitemradio" :aria-checked="status === 'published'" @click="set('published')"><span class="sdot pub"></span>منتشرشده<x-icon i="check" s="14" x-show="status === 'published'" /></button>
      <button type="button" role="menuitemradio" :aria-checked="status === 'draft'" @click="set('draft')"><span class="sdot draft"></span>پیش‌نویس<x-icon i="check" s="14" x-show="status === 'draft'" /></button>
    </div>
  </div>
@endif
