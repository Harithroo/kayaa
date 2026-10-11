{{-- Home, from the prototype's html/index.html. --}}
<x-layouts.store page="home"
  metaDescription="Soft cotton baby clothing from Newborn to 3Y, with a clear size guide. Secure card payment and island-wide delivery across Sri Lanka.">

  <section class="hero" aria-labelledby="hero-title">
    <div class="container hero__grid">
      <div class="hero__copy">
        <p class="eyebrow">New arrivals</p>
        <h1 class="display" id="hero-title">Soft cottons for the first two years.</h1>
        <p class="hero__lede">Breathable cotton and bamboo, cut for Sri Lankan weather. Sized by age, so you order once and it fits.</p>
        <div class="hero__actions">
          <a class="btn btn--primary btn--lg" href="{{ route('shop.index', ['sort' => 'new']) }}">Shop new arrivals</a>
          <a class="link-arrow" href="{{ route('pages.size-guide') }}">Find your baby's size <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
        </div>
      </div>
      <div class="hero__visual">
        {{-- The LCP image when photography exists; until then the design system's placeholder. --}}
        <x-store.photo class="media--round hero__media" :image="$newIn->first()?->primaryImage()" alt="" :tone="2" :eager="true" />
        <div class="hero__tag">
          <span class="hero__tag-icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-ruler"></use></svg></span>
          <p class="hero__tag-text">Sized by age,<br>order once</p>
        </div>
      </div>
    </div>
  </section>

  <section class="trust" aria-label="Why shop with Kayaa">
    <div class="container">
      <ul class="trust__list">
        <li class="trust__item">
          <span class="trust__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-rotate-ccw"></use></svg></span>
          <div><p class="trust__title">Swap sizes free</p><p class="trust__text">Exchange within 14 days, unworn</p></div>
        </li>
        <li class="trust__item">
          <span class="trust__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-shield-check"></use></svg></span>
          <div><p class="trust__title">Tested for sensitive skin</p><p class="trust__text">Soft cotton, gentle on newborns</p></div>
        </li>
        <li class="trust__item">
          <span class="trust__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-truck"></use></svg></span>
          <div><p class="trust__title">Free delivery over {{ money(config('kayaa.free_shipping_over')) }}</p><p class="trust__text">Island-wide by courier</p></div>
        </li>
        <li class="trust__item">
          <span class="trust__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-credit-card"></use></svg></span>
          <div><p class="trust__title">Secure card payment</p><p class="trust__text">Visa &amp; Mastercard accepted</p></div>
        </li>
      </ul>
    </div>
  </section>

  @if($sizes->isNotEmpty())
    <section class="section" aria-labelledby="age-title">
      <div class="container">
        <div class="section__head">
          <div>
            <h2 id="age-title">Shop by age</h2>
            <p class="section__intro">Choose your baby's age band. Height and weight are the better guide, so check the size guide if you are between sizes.</p>
          </div>
        </div>
        <div class="age-steps">
          <div class="age-steps__row">
            <ul class="scroller scroller--age" role="list">
              @foreach($sizes as $i => $size)
                <li style="--step: {{ $i }}">
                  <a class="age-tile" href="{{ route('shop.index', ['size' => $size->label]) }}">
                    <span class="age-tile__label">{{ $size->label }}</span>
                    <span class="age-tile__meta">
                      <span>{{ $size->heightRange() ?? '—' }}</span>
                      <span>{{ $size->weightRange() ?? '—' }}</span>
                    </span>
                    <span class="age-tile__go">Shop <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></span>
                  </a>
                </li>
              @endforeach
            </ul>
          </div>
          <p class="age-steps__more"><a class="link-arrow" href="{{ route('shop.index') }}">See all sizes <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a></p>
        </div>
      </div>
    </section>
  @endif

  @if($tiles->isNotEmpty())
    <section class="section section--surface" aria-labelledby="cat-title">
      <div class="container">
        <div class="section__head">
          <h2 id="cat-title">Shop by category</h2>
        </div>
        <ul class="cat-grid" role="list">
          @foreach($tiles as $tile)
            <li>
              <a class="cat-tile" href="{{ $tile->url() }}">
                <x-store.photo :image="null" alt="" :seed="$tile->id" ratio="4 / 3" />
                <span class="cat-tile__label">{{ $tile->name }} <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></span>
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    </section>
  @endif

  @if($newIn->isNotEmpty())
    <section class="section" aria-labelledby="new-title">
      <div class="container">
        <div class="section__head">
          <h2 id="new-title">New this week</h2>
          <a class="link-arrow" href="{{ route('shop.index', ['sort' => 'new']) }}">View all <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
        </div>
        <ul class="scroller scroller--products" role="list">
          @foreach($newIn as $product)
            <x-store.product-card :product="$product" />
          @endforeach
        </ul>
      </div>
    </section>
  @endif

  @if($promos->isNotEmpty())
    <section class="section" aria-label="Promotions">
      <div class="container promos__grid">
        {{-- Admin > Banners (home_promo). The design system has two promo tints; they
             alternate, as the prototype's pair did. --}}
        @foreach($promos as $i => $promo)
          <article class="promo promo--{{ $i % 2 === 0 ? 'lilac' : 'sky' }}">
            @if($promo->eyebrow)<p class="eyebrow">{{ $promo->eyebrow }}</p>@endif
            <h2 class="promo__title">{{ $promo->title }}</h2>
            @if($promo->body)<p class="promo__text">{{ $promo->body }}</p>@endif
            @if($promo->cta_url)
              <a class="btn {{ $i % 2 === 0 ? 'btn--primary' : 'btn--secondary' }}" href="{{ $promo->cta_url }}">{{ $promo->cta_label ?? 'Shop now' }}</a>
            @endif
          </article>
        @endforeach
      </div>
    </section>
  @endif

  @if($bestsellers->isNotEmpty())
    <section class="section" aria-labelledby="featured-title">
      <div class="container">
        <div class="section__head">
          <h2 id="featured-title">Featured products</h2>
        </div>
        <ul class="product-grid" role="list">
          @foreach($bestsellers as $product)
            <x-store.product-card :product="$product" />
          @endforeach
        </ul>
        <div class="section__foot">
          <a class="btn btn--secondary btn--lg" href="{{ route('shop.index') }}">Shop all</a>
        </div>
      </div>
    </section>
  @endif

</x-layouts.store>
