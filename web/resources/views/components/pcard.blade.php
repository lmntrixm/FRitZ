{{-- Product card (M-List / M-Home). No price, no stock: the action is «افزودن به استعلام». --}}
@props(['p', 'cmp' => false, 'add' => true, 's' => 160, 'w' => null])
@php $card = $p->card(); @endphp
<article {{ $attributes->class(['card', 'pc'])->merge(['style' => $w ? "width:{$w}px" : null]) }}
         @if($cmp) :class="$store.view.cols === 1 && 'row'" @endif>
  @if ($cmp)
    <label class="cmp"><input type="checkbox" class="cbx" :checked="$store.cmp.has({{ $p->id }})" @change="$store.cmp.toggle(@js($card)); $event.target.checked = $store.cmp.has({{ $p->id }})">مقایسه</label>
  @endif
  <a class="pc-open" href="{{ $card['url'] }}">
    <div class="ph"><x-pimg :p="$p" :s="$s" /></div>
    <div class="pc-b">
      <span class="brand">{{ $p->brand->name }}</span>
      <h3 class="pc-n">{{ $p->name }}</h3>
      <div class="pc-meta">
        <span class="code">{{ $p->model_code }}</span>
        <span class="pbs">@foreach ($p->publicProtocols() as $x)<span class="pb">{{ $x->name }}</span>@endforeach</span>
      </div>
    </div>
  </a>
  @if ($add)
    <div class="pc-f">
      <button type="button" class="pc-add" :class="$store.inq.has({{ $p->id }}) && 'in'" @click="$store.inq.toggle(@js($card))" :aria-pressed="$store.inq.has({{ $p->id }})">
        <span x-show="!$store.inq.has({{ $p->id }})"><x-icon i="plus" s="16" sw="2" /></span>
        <span x-show="$store.inq.has({{ $p->id }})" x-cloak><x-icon i="check" s="16" sw="2" /></span>
        <span x-text="$store.inq.has({{ $p->id }}) ? 'در لیست استعلام' : 'افزودن به استعلام'">افزودن به استعلام</span>
      </button>
    </div>
  @endif
</article>
