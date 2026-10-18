@props(['i', 's' => 22, 'sw' => 1.6])
<svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="display:block;flex:none" {{ $attributes }}><path d="{{ \App\Support\Icons::path($i) }}"/></svg>
