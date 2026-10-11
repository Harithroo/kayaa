{{-- Size guide, from the prototype's html/size-guide.html. The chart is the real
     size scale from the admin; the three illustrations are the prototype's own
     artwork, copied to public/assets/img/size-guide. size-guide.js turns the
     steps into a carousel on a narrow screen. --}}
<x-layouts.store title="Size guide" page="size-guide"
  metaDescription="Kayaa baby sizes by age, height and weight, with how to measure and what to do between two sizes.">
  <div class="container content">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="{{ route('home') }}">Home</a></li>
        <li aria-current="page">Size guide</li>
      </ol>
    </nav>
    <header class="content__head">
      <h1>Baby size guide by age, height and weight</h1>
      <p class="content__lede">Our sizes follow age bands, but every baby is different, so height and weight are the better guide. Check both against the chart.</p>
    </header>
    <div class="content__layout">
      <div class="content__main">
        <div class="prose prose--split">
          <h2 id="chart">Size chart <a class="anchor" href="#chart" aria-label="Link to this section: Size chart"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-link"></use></svg></a></h2>
          @if($scale)
            <div class="table-wrap">
              <table class="size-table">
                <caption class="visually-hidden">Size chart</caption>
                <thead><tr><th scope="col">Size</th><th scope="col">Height</th><th scope="col">Weight</th></tr></thead>
                <tbody>
                  @foreach($scale->options as $opt)
                    <tr>
                      <th scope="row">{{ $opt->label }}</th>
                      <td data-label="Height"><span>{{ $opt->heightRange() ?? '—' }}</span></td>
                      <td data-label="Weight"><span>{{ $opt->weightRange() ?? '—' }}</span></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <p>Height in centimetres, weight in kilograms.</p>
          @endif

          <section class="sg-card" aria-labelledby="sg-title">
            <h3 class="sg-card__title" id="sg-title">How to find your size</h3>
            <div class="sg" role="group" aria-roledescription="carousel" aria-label="How to find your size" data-sg>
              <div class="sg__track" tabindex="0" data-sg-track>
                @php
                  $steps = [
                    [1, 'Illustration of a baby lying flat on a blanket with a tape measure running from head to heel, held in place by a hand.', '<strong>Measure height.</strong> With your baby lying flat, measure from the top of the head to the heel.'],
                    [2, 'Illustration of a baby lying on a baby weighing scale beside a clinic record booklet.', '<strong>Check weight.</strong> The weight from the last clinic visit is fine.'],
                    [3, 'Illustration of two baby bodysuits laid flat, a smaller one with an arrow pointing to a larger one that has a check mark above it.', '<strong>Between two sizes?</strong> Go up. A slightly roomy fit is more comfortable in the heat.'],
                  ];
                @endphp
                @foreach($steps as [$n, $alt, $caption])
                  <div class="sg__slide" role="group" aria-roledescription="slide" aria-label="{{ $n }} of 3" id="sg-slide-{{ $n }}">
                    <div class="sg__media">
                      <img src="{{ asset("assets/img/size-guide/size-guide-$n-800.webp") }}"
                           srcset="{{ asset("assets/img/size-guide/size-guide-$n-480.webp") }} 480w, {{ asset("assets/img/size-guide/size-guide-$n-800.webp") }} 800w, {{ asset("assets/img/size-guide/size-guide-$n-1200.webp") }} 1200w"
                           sizes="(min-width: 1100px) 480px, (min-width: 600px) 640px, calc(100vw - 40px)"
                           width="1200" height="900" alt="{{ $alt }}" decoding="async"
                           loading="{{ $n === 1 ? 'eager' : 'lazy' }}" @if($n === 1) fetchpriority="high" @endif>
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
          </section>

          <h2 id="between">Between two sizes? <a class="anchor" href="#between" aria-label="Link to this section: Between two sizes?"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-link"></use></svg></a></h2>
          <div class="notice">
            <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg>
            <div><p>Go up. Babies grow through a band in about six weeks, and a slightly roomy fit is more comfortable in the heat.</p></div>
          </div>
          <p>If you're buying a gift and don't know the height or weight, size by age and choose something with a bit of stretch, like a ribbed bodysuit.</p>
          <p><a class="link-arrow" href="{{ route('shop.index') }}">Shop by size <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a></p>
        </div>
      </div>
    </div>
  </div>
</x-layouts.store>
