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
      @php $updatedAt = config('kayaa.content_updated'); @endphp
      @if($updatedAt)
        <p class="content__updated">Last updated <time datetime="{{ $updatedAt }}">{{ \Illuminate\Support\Carbon::parse($updatedAt)->format('j F Y') }}</time></p>
      @endif
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
            <x-store.size-steps id-prefix="sg" :eager="true" />
          </section>

          <h2 id="between">Between two sizes? <x-store.anchor id="between" label="Between two sizes?" /></h2>
          <div class="notice">
            <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg>
            <div><p>Go up. Babies grow quickly, and a slightly roomy fit is more comfortable in Sri Lanka's heat.</p></div>
          </div>

          <h2 id="measure">How to measure <x-store.anchor id="measure" label="How to measure" /></h2>
          <ol class="how-steps" role="list">
            <li><span class="how-steps__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-ruler"></use></svg></span><h3>Height</h3><p>Lay your baby flat on a firm surface and measure from the top of the head to the heel with a soft tape. A second person helps to keep your baby still.</p></li>
            <li><span class="how-steps__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-scale"></use></svg></span><h3>Weight</h3><p>The weight from the last clinic visit is fine.</p></li>
            <li><span class="how-steps__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-list-checks"></use></svg></span><h3>Compare</h3><p>Find the row that matches both. If they point to different sizes, choose the larger.</p></li>
          </ol>

          <h2 id="gift">Buying a gift? <x-store.anchor id="gift" label="Buying a gift?" /></h2>
          <div class="notice">
            <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg>
            <div><p>If you do not know the height or weight, choose by age and pick something with a little stretch, such as a ribbed bodysuit.</p></div>
          </div>

          @if($navSizes->isNotEmpty())
            <h2 id="sizes">Shop by size <x-store.anchor id="sizes" label="Shop by size" /></h2>
            <ul class="chip-list" role="list">
              @foreach($navSizes as $size)
                <li><a class="chip" href="{{ route('shop.index', ['size' => $size->label]) }}">{{ $size->label }}</a></li>
              @endforeach
            </ul>
          @endif
          @if($navCategories->isNotEmpty())
            <p class="chip-list__label">Or browse by type:</p>
            <ul class="chip-list" role="list">
              @foreach($navCategories as $cat)
                <li><a class="chip" href="{{ $cat->url() }}">{{ $cat->name }}</a></li>
              @endforeach
            </ul>
          @endif

          <h2 id="questions">Size questions <x-store.anchor id="questions" label="Size questions" /></h2>
          <div class="accordion">
            <details class="accordion__item">
              <summary class="accordion__summary">What if my baby is between sizes? <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <div class="accordion__body"><p>Go up. Babies grow quickly and a slightly roomy fit is more comfortable in the heat.</p></div>
            </details>
            <details class="accordion__item">
              <summary class="accordion__summary">Do you size by age or by height and weight? <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <div class="accordion__body"><p>Our sizes follow age bands, but every baby is different, so height and weight are the better guide.</p></div>
            </details>
            <details class="accordion__item">
              <summary class="accordion__summary">Will the clothes shrink? <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <div class="accordion__body"><p>Follow the care label on the garment. Washing cold and drying in the shade keeps the fit and the colour.</p></div>
            </details>
            <details class="accordion__item">
              <summary class="accordion__summary">Can I exchange for a different size? <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <div class="accordion__body"><p>Yes, within 14 days of delivery, and we cover the return courier cost on size swaps. See <a href="{{ route('pages.show', 'returns') }}">returns &amp; exchanges</a>.</p></div>
            </details>
          </div>
        </div>
        <x-store.help-card />
        </div>
      </div>
    </div>
  </div>
</x-layouts.store>
