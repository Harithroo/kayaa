@props(['image' => null, 'tint' => 'sand', 'alt' => '', 'cap' => 'product shot'])
{{-- A product photo, or the design-system striped placeholder until photos are uploaded --}}
<div {{ $attributes->class(['ph', 't-'.$tint => !$image]) }}>
  {{ $slot }}
  @if($image)
    <img src="{{ $image->url() }}" alt="{{ $image->alt ?? $alt }}" loading="lazy">
  @else
    <span class="cap">{{ $cap }}</span>
  @endif
</div>
