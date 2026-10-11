@props(['idPrefix' => 'sg', 'label' => 'How to find your size', 'eager' => false])
{{-- The three illustrated measuring steps, shared by the size guide page and the
     product page's "How to measure your baby" disclosure (prototype: the `sg`
     carousel). size-guide.js turns it into a carousel where it has to scroll. --}}
@php
  $steps = [
    [1, 'Illustration of a baby lying flat on a blanket with a tape measure running from head to heel, held in place by a hand.', '<strong>Measure height.</strong> With your baby lying flat, measure from the top of the head to the heel.'],
    [2, 'Illustration of a baby lying on a baby weighing scale beside a clinic record booklet.', '<strong>Check weight.</strong> The weight from the last clinic visit is fine.'],
    [3, 'Illustration of two baby bodysuits laid flat, a smaller one with an arrow pointing to a larger one that has a check mark above it.', '<strong>Between two sizes?</strong> Go up. A slightly roomy fit is more comfortable in the heat.'],
  ];
@endphp
<div class="sg" role="group" aria-roledescription="carousel" aria-label="{{ $label }}" data-sg>
  <div class="sg__track" tabindex="0" data-sg-track>
    @foreach($steps as [$n, $alt, $caption])
      <div class="sg__slide" role="group" aria-roledescription="slide" aria-label="{{ $n }} of 3" id="{{ $idPrefix }}-slide-{{ $n }}">
        <div class="sg__media">
          <img src="{{ asset("assets/img/size-guide/size-guide-$n-800.webp") }}"
               srcset="{{ asset("assets/img/size-guide/size-guide-$n-480.webp") }} 480w, {{ asset("assets/img/size-guide/size-guide-$n-800.webp") }} 800w, {{ asset("assets/img/size-guide/size-guide-$n-1200.webp") }} 1200w"
               sizes="(min-width: 1100px) 480px, (min-width: 600px) 640px, calc(100vw - 40px)"
               width="1200" height="900" alt="{{ $alt }}" decoding="async"
               loading="{{ $eager && $n === 1 ? 'eager' : 'lazy' }}" @if($eager && $n === 1) fetchpriority="high" @endif>
        </div>
        <p class="sg__caption" data-sg-caption>{!! $caption !!}</p>
      </div>
    @endforeach
  </div>
  <div class="sg__controls" data-sg-controls hidden>
    <button type="button" class="sg__btn" data-sg-prev aria-label="Previous step" aria-disabled="true"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-chevron-left"></use></svg></button>
    <div class="sg__status">
      <ul class="sg__dots" role="list">
        @foreach([1, 2, 3] as $n)
          <li><button type="button" class="sg__dot" data-sg-dot aria-label="Go to step {{ $n }}" @if($n === 1) aria-current="true" @endif></button></li>
        @endforeach
      </ul>
      <p class="sg__count"><span data-sg-now>1</span> of 3</p>
    </div>
    <button type="button" class="sg__btn" data-sg-next aria-label="Next step"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-chevron-right"></use></svg></button>
  </div>
  <p class="visually-hidden" role="status" aria-live="polite" data-sg-live></p>
</div>
