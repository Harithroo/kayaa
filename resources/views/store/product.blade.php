{{-- Product page, from the prototype's html/product.html.

     The picker is the prototype's: colour swatches and size boxes, with
     product.js reading #product-variants to disable sold-out sizes, show the
     stock note and drive the sticky bar. The values are the real colour and size
     option ids, and the form posts them to the cart, which resolves the pair to
     a variant (CartController::quickAdd). --}}
@php
  $variants = $product->activeVariants();
  $colors = $product->colors();
  $sizes = $product->sizeOptions();
  $price = $product->minPrice();
  $was = $product->compareAtPrice();
  $onSale = $was !== null && $was > $price;
  $inStock = $product->totalStock() > 0;
  $rating = $product->ratingAverage();
  $ratingCount = $product->ratingCount();
  $images = $product->images;
  // The gallery always has at least one slide, so the dots and thumbs line up with it.
  $slides = $images->isNotEmpty() ? $images : collect([null]);

  // What product.js needs: one row per variant, keyed the same way as the radios.
  $variantJson = $variants->map(fn ($v) => [
    'colour' => (string) ($v->color_id ?? 0),
    'size' => (string) $v->size_option_id,
    'stock' => (int) $v->stock,
  ])->values();

  $reviewTotal = max(1, $ratingCount);
@endphp
<x-layouts.store :title="$product->name" page="product"
  :metaDescription="Str::limit(strip_tags($product->description ?? ''), 155) ?: null">

  <div class="container product" data-product-page>

    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="{{ route('home') }}">Home</a></li>
        <li><a href="{{ route('categories.index') }}">{{ $product->category?->department?->name ?? 'Shop' }}</a></li>
        @if($product->category)<li><a href="{{ $product->category->url() }}">{{ $product->category->name }}</a></li>@endif
        <li aria-current="page">{{ $product->name }}</li>
      </ol>
    </nav>
    @if($product->category)
      <a class="back-link" href="{{ $product->category->url() }}"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-left"></use></svg> Back to {{ $product->category->name }}</a>
    @endif

    <div class="product__top">

      <section class="gallery" aria-roledescription="carousel" aria-label="Product photos" data-gallery>
        <div class="gallery__stage">
          <div class="gallery__track" role="group" aria-label="Photo gallery" tabindex="0" data-gallery-track>
            @foreach($slides as $i => $image)
              <div class="gallery__slide" data-colour="{{ $image?->color_id ?? 'all' }}" role="group" aria-roledescription="slide" aria-label="Photo {{ $i + 1 }} of {{ $slides->count() }}">
                <x-store.photo :image="$image" :alt="$product->name" :seed="$product->id + $i" :eager="$i === 0" />
              </div>
            @endforeach
          </div>
          @if(! $inStock)
            <span class="badge gallery__badge" data-product-badge>Out of stock</span>
          @elseif($onSale)
            <span class="badge badge--accent gallery__badge" data-product-badge>Sale</span>
          @elseif($product->is_new)
            <span class="badge badge--lilac gallery__badge" data-product-badge>New</span>
          @endif
          <p class="gallery__count" data-gallery-count aria-hidden="true">1 / {{ $slides->count() }}</p>
        </div>
        {{-- One dot and one thumb per slide: product.js pairs them by index. --}}
        <div class="gallery__dots">
          @foreach($slides as $i => $image)
            <button type="button" class="gallery__dot" data-gallery-dot aria-label="Show photo {{ $i + 1 }} of {{ $slides->count() }}" @if($i === 0) aria-current="true" @endif></button>
          @endforeach
        </div>
        <ul class="gallery__thumbs" role="list" aria-label="Photo thumbnails" data-gallery-thumbs>
          @foreach($slides as $i => $image)
            <li>
              <button type="button" class="gallery__thumb" data-colour="{{ $image?->color_id ?? 'all' }}" data-gallery-thumb
                      aria-label="Show photo {{ $i + 1 }} of {{ $slides->count() }}" @if($i === 0) aria-current="true" @else tabindex="-1" @endif>
                <x-store.photo :image="$image" alt="" :seed="$product->id + $i" />
              </button>
            </li>
          @endforeach
        </ul>
      </section>

      <div class="product__info">
        @if($product->collection_label)<p class="product__eyebrow">{{ $product->collection_label }}</p>@endif
        <h1 class="product__title">{{ $product->name }}</h1>

        @if($rating)
          <a class="rating-link" href="#reviews" data-rating-summary>
            <x-store.stars :rating="$rating" />
            <span aria-hidden="true">{{ number_format($rating, 1) }} · {{ $ratingCount }} {{ Str::plural('review', $ratingCount) }}</span>
            <span class="visually-hidden">Rated {{ number_format($rating, 1) }} out of 5 from {{ $ratingCount }} {{ Str::plural('review', $ratingCount) }}</span>
          </a>
        @endif

        <div class="product__price-row" data-price-row>
          <p @class(['price', 'price--lg', 'price--sale' => $onSale])>
            @if($onSale)<span class="visually-hidden">Sale price </span>@endif
            <span class="price__now" data-price-now>{{ money($price) }}</span>
            @if($onSale) <s class="price__was" data-price-was><span class="visually-hidden">Was </span>{{ money($was) }}</s>@endif
          </p>
          @if($onSale)<span class="badge badge--lilac">Save {{ money($was - $price) }}</span>@endif
        </div>

        @if($product->description)
          <p class="product__lede">{{ Str::limit(strip_tags($product->description), 160) }}</p>
        @endif

        {{-- product.js reads this: one row per variant, same values as the radios. --}}
        <script type="application/json" id="product-variants">@json($variantJson)</script>

        <form class="buy" method="post" action="{{ route('cart.quick-add') }}" data-buy-form
              data-product-name="{{ $product->name }}"
              data-product-slug="{{ $product->slug }}"
              data-unit-price="{{ (int) ($price / 100) }}"
              @if($onSale) data-was-price="{{ (int) ($was / 100) }}" @endif
              data-low-stock-threshold="{{ \App\Models\ProductVariant::lowStockThreshold() }}"
              data-cart-form novalidate>
          @csrf
          <input type="hidden" name="product" value="{{ $product->id }}">

          <fieldset class="buy__group" @if($colors->isEmpty()) hidden @endif>
            <legend class="buy__legend">Colour — <span data-colour-label>{{ $colors->first()?->name }}</span></legend>
            <div class="buy__options buy__options--colours">
              @forelse($colors as $i => $color)
                <label class="swatch">
                  <input class="visually-hidden" type="radio" name="colour" value="{{ $color->id }}" @checked($i === 0)>
                  <span class="dot" @if($color->hex) style="background: {{ $color->hex }}" @endif aria-hidden="true"></span>
                  <span class="visually-hidden">{{ $color->name }}</span>
                </label>
              @empty
                {{-- No colours on this product: one hidden option keeps the picker's contract. --}}
                <label class="swatch"><input class="visually-hidden" type="radio" name="colour" value="0" checked><span class="dot" aria-hidden="true"></span><span class="visually-hidden">One colour</span></label>
              @endforelse
            </div>
          </fieldset>

          <div class="buy__group">
            <div class="buy__legend-row">
              <span class="buy__legend" id="size-label">Size</span>
              <button type="button" class="link-btn" data-size-guide-toggle aria-expanded="false" aria-controls="size-guide-panel">Size guide</button>
            </div>
            <div class="buy__options" role="radiogroup" aria-labelledby="size-label" id="size-group" tabindex="-1" data-size-group>
              @foreach($sizes as $size)
                <label class="size-box size-box--lg">
                  <input class="visually-hidden" type="radio" name="size" value="{{ $size->id }}">
                  <span>{{ $size->label }}</span>
                  <span class="visually-hidden" data-oos-text hidden> (out of stock)</span>
                </label>
              @endforeach
            </div>
            <p class="buy__error" id="size-error" role="alert" data-size-error hidden>
              <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg> <span>Please choose a size</span>
            </p>
          </div>

          <div class="size-guide" id="size-guide-panel" data-size-guide hidden>
            <table class="size-table">
              <caption class="visually-hidden">Size guide by age, height and weight</caption>
              <thead><tr><th scope="col">Size</th><th scope="col">Height</th><th scope="col">Weight</th></tr></thead>
              <tbody>
                @foreach(($product->sizeScale?->options ?? $sizes) as $size)
                  <tr><th scope="row">{{ $size->label }}</th><td>{{ $size->heightRange() ?? '—' }}</td><td>{{ $size->weightRange() ?? '—' }}</td></tr>
                @endforeach
              </tbody>
            </table>
            <p class="size-guide__units">Height in centimetres, weight in kilograms.</p>
            <a class="link-arrow" href="{{ route('pages.size-guide') }}">Open the full size guide <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
          </div>

          <p class="buy__stock" id="stock-note" aria-live="polite" data-stock-note data-in-stock-text="In stock · ships from Colombo">Choose a size to see availability</p>

          <div class="buy__row">
            <div class="qty" data-qty data-qty-max="10">
              <button type="button" class="qty__btn" data-qty-minus aria-label="Decrease quantity" disabled><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-minus"></use></svg></button>
              <input class="qty__input" type="text" inputmode="numeric" pattern="[0-9]*" name="quantity" value="1" data-qty-input aria-label="Quantity">
              <button type="button" class="qty__btn" data-qty-plus aria-label="Increase quantity"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-plus"></use></svg></button>
            </div>
            <button type="submit" class="btn btn--primary btn--lg buy__submit" data-add-btn @disabled(! $inStock)>{{ $inStock ? 'Add to cart' : 'Out of stock' }}</button>
          </div>
          <p class="buy__dispatch">Order before 2pm for same-day dispatch</p>
        </form>

        {{-- Save is its own form: the wishlist lives on the server. --}}
        <form method="post" action="{{ route('wishlist.toggle', $product) }}" class="buy__save-form">
          @csrf
          <button type="submit" class="btn btn--secondary btn--lg buy__save" data-wishlist-toggle aria-pressed="{{ $isWishlisted ?? false ? 'true' : 'false' }}">
            <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-heart"></use></svg>
            <span data-save-label>{{ ($isWishlisted ?? false) ? 'Saved' : 'Save' }}</span>
          </button>
        </form>

        <ul class="assure" role="list">
          <li><span class="assure__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-truck"></use></svg></span><span>Free delivery over {{ money(config('kayaa.free_shipping_over')) }}</span></li>
          <li><span class="assure__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-credit-card"></use></svg></span><span>Secure card payment · Visa &amp; Mastercard</span></li>
          <li><a class="assure__link" href="{{ route('pages.show', 'returns') }}"><span class="assure__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-rotate-ccw"></use></svg></span><span>Easy returns · see our returns page</span></a></li>
        </ul>

        <div class="accordion product__accordion">
          @if($product->fabric_care)
            <details class="accordion__item" open>
              <summary class="accordion__summary">Fabric &amp; care <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <div class="accordion__body">{!! nl2br(e($product->fabric_care)) !!}</div>
            </details>
          @endif
          @if($product->description)
            <details class="accordion__item" @if(! $product->fabric_care) open @endif>
              <summary class="accordion__summary">Description <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <div class="accordion__body">{!! nl2br(e($product->description)) !!}</div>
            </details>
          @endif
          <details class="accordion__item">
            <summary class="accordion__summary">Delivery &amp; returns <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
            <div class="accordion__body">
              <p>Free over {{ money(config('kayaa.free_shipping_over')) }}, otherwise {{ money(config('kayaa.shipping_fee')) }}.</p>
              <p>Exchange any unworn item within 14 days with tags on.</p>
              <p><a class="link-arrow" href="{{ route('pages.show', 'delivery') }}">Delivery information <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a></p>
              <p><a class="link-arrow" href="{{ route('pages.show', 'returns') }}">Returns information <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a></p>
            </div>
          </details>
          @foreach($faqs as $faq)
            <details class="accordion__item">
              <summary class="accordion__summary">{{ $faq->question }} <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <div class="accordion__body">{!! nl2br(e($faq->answer)) !!}</div>
            </details>
          @endforeach
        </div>
      </div>
    </div>

    <section class="reviews" id="reviews" aria-labelledby="reviews-title">
      <h2 id="reviews-title">Reviews</h2>

      <div class="reviews__layout">
        <div class="rating-summary" data-reviews-summary>
          @if($rating)
            <div class="rating-summary__score">
              <p class="rating-summary__avg">{{ number_format($rating, 1) }}</p>
              <x-store.stars :rating="$rating" size="lg" :label="'Rated '.number_format($rating, 1).' out of 5'" />
            </div>
            <p class="rating-summary__based">Based on {{ $ratingCount }} {{ Str::plural('review', $ratingCount) }}</p>
            <ul class="rating-rows" role="list">
              @for($star = 5; $star >= 1; $star--)
                @php $n = (int) ($ratingBreakdown[$star] ?? 0); @endphp
                <li class="rating-row">
                  <span class="rating-row__label">{{ $star }} {{ Str::plural('star', $star) }}</span>
                  <progress class="progress" value="{{ $n }}" max="{{ $reviewTotal }}" aria-hidden="true">{{ $n }}</progress>
                  <span class="rating-row__count">{{ $n }}</span>
                </li>
              @endfor
            </ul>
          @else
            <p class="rating-summary__based">No reviews yet. Be the first.</p>
          @endif
          <a class="btn btn--secondary btn--block" href="#write-review">Write a review</a>
        </div>

        <div>
          @if($reviews->isNotEmpty())
            <div data-reviews-content>
              <ul class="reviews__list" role="list">
                @foreach($reviews as $review)
                  <li class="review-card">
                    <article tabindex="-1">
                      <header class="review-card__head">
                        <x-store.stars :rating="$review->rating" :label="'Rated '.$review->rating.' out of 5'" />
                        <p class="review-card__name">{{ $review->name }}</p>
                        <time class="review-card__date" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('j M Y') }}</time>
                        @if($review->is_verified_purchase)<span class="badge badge--outline">Verified purchase</span>@endif
                      </header>
                      @if($review->title)<p class="review-card__headline">{{ $review->title }}</p>@endif
                      <p class="review-card__text">{{ $review->body }}</p>
                      @if($review->admin_reply)
                        <div class="review-card__reply">
                          <p class="review-card__reply-title">Reply from Kayaa</p>
                          <p>{{ $review->admin_reply }}</p>
                        </div>
                      @endif
                    </article>
                  </li>
                @endforeach
              </ul>
            </div>
          @else
            <div class="empty-state">
              <span class="empty-state__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg></span>
              <h3 class="empty-state__title">No reviews yet</h3>
              <p>Bought this? Tell other parents how it fits.</p>
            </div>
          @endif

          <section class="review-form" id="write-review" aria-labelledby="write-review-title" tabindex="-1">
            <h3 id="write-review-title">Write a review</h3>

            <div class="review-form__alerts">
              @if($errors->any())
                <div class="alert alert--error" role="alert" tabindex="-1">
                  <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
                  <div>
                    <p class="alert__title">Please check your review</p>
                    <ul class="alert__list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                  </div>
                </div>
              @endif
              @if($hasReviewed)
                <div class="alert alert--info">
                  <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg>
                  <div><p>You've already reviewed this product.</p></div>
                </div>
              @endif
            </div>

            @unless($hasReviewed)
              <form method="post" action="{{ route('reviews.store', $product) }}" novalidate data-review-form>
                @csrf
                <div class="review-form__fields">
                  <fieldset class="rating-input" data-field="rating">
                    <legend>Your rating</legend>
                    <div class="rating-input__stars">
                      @for($star = 1; $star <= 5; $star++)
                        <input type="radio" name="rating" id="rating-{{ $star }}" value="{{ $star }}" required @checked(old('rating') == $star)>
                        <label for="rating-{{ $star }}"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg><span class="visually-hidden">{{ $star }} {{ Str::plural('star', $star) }}</span></label>
                      @endfor
                    </div>
                  </fieldset>
                  @guest
                    <div class="field" data-field="name">
                      <label for="review-name">Name</label>
                      <input id="review-name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="field" data-field="email">
                      <label for="review-email">Email</label>
                      <input id="review-email" name="email" type="email" inputmode="email" autocomplete="email" value="{{ old('email') }}" aria-describedby="email-hint">
                      <p class="field__hint" id="email-hint">Not shown on the page.</p>
                    </div>
                  @endguest
                  <div class="field" data-field="headline">
                    <label for="review-headline">Headline (optional)</label>
                    <input id="review-headline" name="title" type="text" maxlength="120" autocomplete="off" value="{{ old('title') }}">
                  </div>
                  <div class="field" data-field="body">
                    <label for="review-body">Your review</label>
                    <textarea id="review-body" name="body" rows="5" required aria-describedby="body-hint">{{ old('body') }}</textarea>
                    <p class="field__hint" id="body-hint">Reviews are checked by us before they appear.</p>
                  </div>
                </div>
                <button type="submit" class="btn btn--primary">Submit review</button>
              </form>
            @endunless
          </section>
        </div>
      </div>
    </section>

    @if($related->isNotEmpty())
      <section class="related" aria-labelledby="related-title">
        <h2 id="related-title">Goes well with</h2>
        <ul class="scroller" role="list">
          @foreach($related as $item)
            <x-store.product-card :product="$item" />
          @endforeach
        </ul>
      </section>
    @endif

  </div>

  {{-- Sticky add-to-cart bar, shown below 900px once the main button scrolls away. --}}
  <div class="sticky-atc" data-sticky-atc inert>
    <div class="sticky-atc__info">
      <p class="sticky-atc__price" data-sticky-price>{{ money($price) }}</p>
      <p class="sticky-atc__size" data-sticky-size>Select a size</p>
    </div>
    <button type="button" class="btn btn--primary" data-sticky-btn>Choose size</button>
  </div>
</x-layouts.store>
