@props([
  'image' => null,
  'alt' => '',
  'tone' => null,      // 1-4; falls back to a stable tone derived from $seed
  'seed' => 0,
  'ratio' => null,     // e.g. '1 / 1' to override the 4:5 product ratio
  'eager' => false,    // above-the-fold images skip lazy loading
  'width' => 800,
  'height' => 1000,
])
@php
  // The design system paints placeholders in four lilac tones. Without an explicit
  // tone, derive one from the record id so a product keeps the same tone everywhere.
  $tone = $tone ?: (((int) $seed % 4) + 1);
  $style = '--tone: var(--tone-'.$tone.')'.($ratio ? '; --ratio: '.$ratio : '');
@endphp
<div {{ $attributes->class(['media'])->merge(['style' => $style]) }} @unless($image) data-placeholder @endunless>
  @if($image)
    <img src="{{ $image->url() }}" alt="{{ $image->alt ?? $alt }}" width="{{ $width }}" height="{{ $height }}"
         loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" @if($eager) fetchpriority="high" @endif>
  @else
    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-image"></use></svg>
  @endif
  {{ $slot }}
</div>
