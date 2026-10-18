{{-- A product's primary photo, or its category's placeholder drawing. --}}
@props(['p', 's' => 160, 'eager' => false])
@php $img = $p->images()->first(); @endphp
@if ($img)
  <img src="{{ $img->url() }}" alt="{{ $img->alt ?: $p->name }}" @if($img->width) width="{{ $img->width }}" height="{{ $img->height }}" @endif loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async">
@else
  <x-art :kind="$p->art()" :s="$s" :label="$p->name" />
@endif
