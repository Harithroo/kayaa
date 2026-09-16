@props(['image' => null, 'tint' => 't1', 'alt' => ''])
{{-- A product photo, or the placeholder until photos are uploaded --}}
<div {{ $attributes->class(['ph', $tint => !$image, 'empty' => !$image]) }}>
  {{ $slot }}
  @if($image)
    <img src="{{ $image->url() }}" alt="{{ $image->alt ?? $alt }}" loading="lazy">
  @else
    <svg viewBox="0 0 100 120" fill="none" stroke="#1E2B24" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M32 16 C32 10, 68 10, 68 16 L82 26 L74 42 L68 38 L68 76 C68 88, 58 96, 50 96 C42 96, 32 88, 32 76 L32 38 L26 42 L18 26 Z"/><path d="M38 84 h24"/><path d="M42 16 c4 6, 12 6, 16 0"/></svg>
  @endif
</div>
