{{-- Placeholder drawing from the sprite in partials/art-sprite (until real photos arrive). --}}
@props(['kind' => 'panel7', 's' => 200, 'label' => 'تصویر محصول'])
<svg class="art" width="{{ $s }}" height="{{ $s }}" viewBox="0 0 200 200" role="img" aria-label="{{ $label }}" {{ $attributes }}><use href="#art-{{ $kind }}"/></svg>
