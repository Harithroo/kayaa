@props(['rating' => 0, 'size' => null, 'label' => null])
{{-- The design system's star row (prototype: product.html). A half star is two
     overlaid icons, so .star-half can clip the filled one. --}}
@php
  $rating = (float) $rating;
  $full = (int) floor($rating);
  $half = ($rating - $full) >= 0.25 && ($rating - $full) < 0.75;
  if (($rating - $full) >= 0.75) { $full++; }
@endphp
<span {{ $attributes->class(['stars', 'stars--lg' => $size === 'lg']) }}
      @if($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
  @for($i = 1; $i <= 5; $i++)
    @if($i <= $full)
      <svg class="icon is-on" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg>
    @elseif($half && $i === $full + 1)
      <span class="star-half">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg>
        <svg class="icon is-on" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg>
      </span>
    @else
      <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg>
    @endif
  @endfor
</span>
