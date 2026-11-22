{{-- Home, from the prototype's html/index.html (frontend branch, after the Home page v2
     work: design/home-v2-concept.html is the client-approved reference). The markup is
     the prototype's; what it generated with tools/catalogue.mjs (the gen: home-* and
     product-grid regions) comes from the real records in HomeController.

     Left out on purpose, as the prototype marks them for removal before launch: the
     motion pill and its sessionStorage override, the visible "Proposed" marks on the
     fabric copy, and the three sample reviews (the real reviews feed replaces them). --}}
@php
  $freeOver = money((int) config('kayaa.free_shipping_over'));
  $waHref = \App\Support\StoreContact::whatsappHref();
  $sizeRange = $sizes->count() > 2
    ? $sizes->first()->label.', '.$sizes->get(1)->label.' … '.$sizes->last()->label
    : $sizes->pluck('label')->join(', ');
  $age = $ages[1] ?? null;
  $lists = array_filter([
    'new' => ['title' => 'New this week', 'products' => $newIn],
    'best' => ['title' => 'Bestsellers', 'products' => $bestsellers],
  ], fn ($l) => $l['products']->isNotEmpty());
  $star = '<polygon points="12 3 14.9 9.3 21.8 9.9 16.6 14.5 18.1 21.3 12 17.8 5.9 21.3 7.4 14.5 2.2 9.9 9.1 9.3"/>';
  $flower = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2c1.6 3 1.6 5 0 8-1.6-3-1.6-5 0-8Zm0 12c1.6 3 1.6 5 0 8-1.6-3-1.6-5 0-8ZM2 12c3-1.6 5-1.6 8 0-3 1.6-5 1.6-8 0Zm12 0c3-1.6 5-1.6 8 0-3 1.6-5 1.6-8 0Z"/></svg>';
@endphp
<x-layouts.store page="home" :meta="$meta" :json-ld="$jsonLd">

  @push('head')
    <script>
      /* Motion: follow the device setting. Decided before first paint so nothing flashes (Home only).
         html.rm = reduced motion (everything shows its finished state), html.intro-on = the preloader plays (first visit per session, never under reduced motion). */
      (function () {
        var d = document.documentElement, rm = false, seen = null;
        try { seen = sessionStorage.getItem('kayaa-intro'); } catch (e) { /* storage blocked */ }
        rm = !!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
        d.classList.add('js');
        if (rm) d.classList.add('rm');
        if (!rm && !seen) d.classList.add('intro-on');
        window.KAYAA_RM = rm;
      })();
    </script>
    @vite(['resources/css/home.css', 'resources/js/home.js'])
  @endpush

  {{-- Preloader: first visit per session only (html.intro-on is set by the head script, never under reduced motion). Decorative: aria-hidden,
       the page underneath is fully rendered. A click skips it. --}}
  <div class="intro" id="intro" aria-hidden="true">
    <svg viewBox="0 0 560 170"><path class="i-line" pathLength="1" d="M10 30 Q 280 70 550 30"/><g id="introGs"></g></svg>
    <div class="i-word"><span>k</span><span>a</span><span>y</span><span>a</span><span>a</span></div>
    <small>Soft cottons for the first two years</small>
  </div>

  {{-- Hero: the words rise in turn (CSS, delayed by the preloader), an accent underline draws under the last word, and a washing line sways in an arch
       (home-hero.js). The arch replaces the hero photo. --}}
  <section class="hero" aria-labelledby="hero-title" data-live>
    <div class="container hero__grid">
      <div class="hero__copy">
        <p class="eyebrow hero__in" style="--d: 0">New arrivals</p>
        <h1 class="hero__title" id="hero-title"><span class="w"><span style="--wi: 0">Soft</span></span> <span class="w"><span style="--wi: 1">cottons</span></span> <span class="w"><span style="--wi: 2">for</span></span> <span class="w"><span style="--wi: 3">the</span></span> <span class="hl"><span class="w"><span style="--wi: 4">first</span></span> <span class="w"><span style="--wi: 5">two</span></span> <span class="ul"><span class="w"><span style="--wi: 6">years.</span></span><svg class="hl-line" viewBox="0 0 200 20" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path pathLength="1" d="M3 14 C 50 4, 120 4, 197 11"/></svg></span></span></h1>
        {{-- TODO: confirm with client (fabric claims: breathable cotton and bamboo, cut for Sri Lankan weather) --}}
        <p class="hero__lede hero__in" style="--d: 380">Breathable cotton and bamboo, cut for Sri Lankan weather. Sized by age, so you order once and it fits.</p>
        <div class="hero__actions hero__in" style="--d: 480">
          <a class="btn btn--primary btn--lg" href="{{ route('shop.index', ['sort' => 'new']) }}">Shop new arrivals</a>
          <a class="link-arrow" href="{{ route('pages.size-guide') }}">Find your baby's size <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
        </div>
      </div>
      <div class="hero__visual" id="heroVisual">
        <span class="hero__blob hero__blob--1" aria-hidden="true"></span><span class="hero__blob hero__blob--2" aria-hidden="true"></span>
        <div class="hero__arch" id="arch" aria-hidden="true"><svg class="hero__line" id="heroLine" preserveAspectRatio="none" focusable="false"><path id="heroLinePath" d=""/></svg></div>
        @if($sizeRange !== '')
          <p class="hero-tag hero-tag--sized" style="--d: 800" aria-hidden="true"><b>Sized by age</b><span>{{ $sizeRange }}</span></p>
        @endif
        <p class="hero-tag hero-tag--delivery" style="--d: 950" aria-hidden="true"><b>{{ $freeOver }}+</b><span>Free delivery</span></p>
        @if($fabrics)
          <a class="hero-tag hero-tag--fabrics" href="#fabrics" style="--d: 1100"><span class="swatches-stack" id="heroSw" aria-hidden="true"><i></i><i></i><i></i></span><span><b>Feel the fabrics</b><span>Muslin, bamboo, rib, waffle</span></span></a>
        @endif
      </div>
    </div>
  </section>

  {{-- Trust strip: the icons draw in once (pathLength 1), staggered and offset by the preloader --}}
  <section class="trust" aria-label="Why shop with Kayaa">
    <div class="container">
      <ul class="trust__list">
        <li class="trust__item">
          <span class="trust__icon" style="--d: 700"><svg class="icon icon--24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path pathLength="1" d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path pathLength="1" d="M21 3v5h-5"/><path pathLength="1" d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path pathLength="1" d="M3 21v-5h5"/></svg></span>
          <div><p class="trust__title">Swap sizes free</p><p class="trust__text">Exchange within 14 days, unworn</p></div>
        </li>
        <li class="trust__item">
          <span class="trust__icon" style="--d: 820"><svg class="icon icon--24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path pathLength="1" d="M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3Z"/><polyline pathLength="1" points="9 12 11 14 15 9.5"/></svg></span>
          {{-- The prototype says "OEKO-TEX certified cotton", which needs the client's certificate before it can be shown. --}}
          <div><p class="trust__title">Tested for sensitive skin</p><p class="trust__text">Soft cotton, gentle on newborns</p></div>
        </li>
        <li class="trust__item">
          <span class="trust__icon" style="--d: 940"><svg class="icon icon--24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect pathLength="1" x="1.5" y="7" width="13" height="8" rx="1"/><path pathLength="1" d="M14.5 10h4l3 3v2h-7z"/><circle pathLength="1" cx="6" cy="17.5" r="1.6"/><circle pathLength="1" cx="17" cy="17.5" r="1.6"/></svg></span>
          <div><p class="trust__title">Free delivery over {{ $freeOver }}</p><p class="trust__text">Island-wide by courier</p></div>
        </li>
        <li class="trust__item">
          <span class="trust__icon" style="--d: 1060"><svg class="icon icon--24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect pathLength="1" x="3" y="6" width="18" height="13" rx="2"/><path pathLength="1" d="M3 10h18"/><path pathLength="1" d="M7 15h3"/></svg></span>
          <div><p class="trust__title">Secure card payment</p><p class="trust__text">Visa &amp; Mastercard accepted</p></div>
        </li>
      </ul>
    </div>
  </section>

  {{-- Shop by age: one chip per size; the panel shows the size, height and weight (counting up), the Shop button and a growing outfit on a peg line.
       The ages, links, heights and weights are the size chart (Admin > Size scales), the same records as the size guide. --}}
  @if($age)
    <section class="section age" aria-labelledby="age-title">
      <div class="container">
        <div class="section__head">
          <div>
            <p class="eyebrow">Shop by age</p>
            <h2 class="split age__title" id="age-title">How old is your little one?</h2>
            <p class="section__intro" data-reveal>Pick an age and watch the size grow. Height and weight are the better guide if your baby is between sizes.</p>
          </div>
        </div>
        <div class="age-chips" id="ageChips" role="group" aria-label="Choose an age">
          @foreach($ages as $i => $a)
            <button class="age-chip" type="button" aria-pressed="{{ $i === 1 ? 'true' : 'false' }}" data-i="{{ $i }}">{{ $a['label'] }}</button>
          @endforeach
        </div>
        <div class="age-panel" data-reveal>
          <div class="age-swap" id="ageText" aria-live="polite">
            <p class="age-name" id="ageName">{{ $age['name'] }}</p>
            <p class="age-meta" id="ageMeta">Height <b>{{ $age['height'] }}</b> · Weight <b>{{ $age['weight'] }}</b></p>
            <div class="age-actions">
              <a class="btn btn--primary" id="ageShop" href="{{ $age['href'] }}">Shop {{ $age['label'] }}</a>
              <a class="link-arrow" href="{{ route('pages.size-guide') }}">Size guide <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
            </div>
          </div>
          <div class="age-grow" aria-hidden="true"><span class="age-grow__line"></span><div class="age-grow__g" id="growG"></div><span class="age-grow__mark" id="growMark"></span></div>
          <div class="age-ruler" aria-hidden="true">
            <div class="age-ruler__row">
              <label>Height <output id="hOut"></output></label>
              <div class="age-track"><span class="age-track__band" id="hBand"></span></div>
              <div class="age-ticks"><span>45</span><span>60</span><span>75</span><span>90</span><span>105 cm</span></div>
            </div>
            <div class="age-ruler__row">
              <label>Weight <output id="wOut"></output></label>
              <div class="age-track"><span class="age-track__band" id="wBand"></span></div>
              <div class="age-ticks"><span>2</span><span>6</span><span>10</span><span>14</span><span>18 kg</span></div>
            </div>
          </div>
        </div>
        <script type="application/json" id="age-data">@json($ages)</script>
      </div>
    </section>
  @endif

  {{-- Shop by category: the rail. One real link per category, drawn and moved by home-rail.js. Without scripts the links show as a plain list
       (.rail-fallback). A cut-out photo at public/assets/img/rail/{slug}.webp replaces a drawing (data-photo). --}}
  @if($rail->isNotEmpty())
    <section class="section section--rail" id="rail" aria-labelledby="rail-title">
      <div class="container">
        <div class="section__head">
          <div>
            <p class="eyebrow">Shop by category</p>
            <h2 class="split rail__title" id="rail-title">Pick something off the rail</h2>
            <p class="section__intro" data-reveal id="railSub">Hover a hanger to turn it around. Brush along the rail to make them swing, or drag one along.</p>
          </div>
          <div class="rail-head-actions" data-reveal>
            <button class="rail-replay" type="button" id="replay"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-rotate-ccw"></use></svg><span>Close the doors</span></button>
            <a class="link-arrow" href="{{ route('categories.index') }}">All categories <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
          </div>
        </div>
        <div class="rail-wrap">
          <div class="rail-stage" id="railStage" style="--rail-n: {{ $rail->count() }}">
            <div class="rail-floor" id="railFloor" aria-hidden="true"></div>
            <div class="rail-scroller" id="railScroller">
              <div class="rail-inner" id="railInner">
                <div class="rail-layer back" id="railBack" aria-hidden="true"></div>
                <div class="rail-rod" id="railRod" aria-hidden="true"><span class="cap l"></span><span class="cap r"></span></div>
                <nav class="rail-layer front" id="railFront" aria-label="Categories on the rail">
                  @foreach($rail as $i => $c)
                    <a class="hanger" href="{{ $c->url }}" draggable="false" data-i="{{ $i }}" data-name="{{ $c->name }}" data-count="{{ $c->count }}" data-range="{{ $c->range ?? '' }}" data-meta="{{ $c->meta }}" data-type="{{ $c->type }}" data-c="{{ $c->c }}"{!! $c->c2 ? ' data-c2="'.e($c->c2).'"' : '' !!}{!! $c->photo ? ' data-photo="'.e($c->photo).'"' : '' !!} aria-label="{{ $c->label }}"><span class="turn"></span><span class="hit"></span></a>
                  @endforeach
                </nav>
              </div>
            </div>
            <div class="rail-caption" id="railCaption"><div class="cap-body" id="capBody"></div></div>
            <div class="rail-arrows">
              <button class="rail-arrow" type="button" id="railPrev" aria-label="Previous hangers"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-left"></use></svg></button>
              <button class="rail-arrow" type="button" id="railNext" aria-label="Next hangers"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-right"></use></svg></button>
            </div>
            <div class="doors" id="doors">
              <div class="door l" aria-hidden="true"></div><div class="door r" aria-hidden="true"></div>
              <button class="door-open-btn" type="button" id="doorBtn"><span class="knob">k</span>Open the wardrobe</button>
            </div>
          </div>
          <ul class="rail-fallback" role="list">
            @foreach($rail as $c)
              <li><a href="{{ $c->url }}">{{ $c->name }} <span>{{ $c->meta }}</span></a></li>
            @endforeach
          </ul>
          <div class="rail-foot">
            <p>{{ ucfirst(\Illuminate\Support\Number::spell($rail->count())) }} {{ \Illuminate\Support\Str::plural('category', $rail->count()) }}, from the first bodysuit to the first cardigan.</p>
            <span class="rail-progress" aria-hidden="true"><i id="railBar"></i></span>
          </div>
        </div>
      </div>
    </section>
  @endif

  {{-- Scrolling band: the messages are config/kayaa.php home.marquee; the second list is a visual copy (aria-hidden).
       Moves at 42px/s, speeds up with the page scroll, has a Pause button (WCAG 2.2.2); under reduced motion it is one static wrapped line. --}}
  @if($marquee)
    <div class="marquee">
      <div class="marquee__track" id="mqTrack">
        @foreach([false, true] as $copy)
          <ul class="marquee__set" role="list" @if($copy) aria-hidden="true" @endif>@foreach($marquee as $i => $message)<li><span class="marquee__word{{ $i % 2 ? ' marquee__word--o' : '' }}">{{ $message }}</span>{!! $flower !!}</li>@endforeach</ul>
        @endforeach
      </div>
      <button class="marquee__pause" type="button" id="mqPause" aria-label="Pause the moving text" aria-pressed="false"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><line x1="9" y1="6" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="18"/></svg></button>
    </div>
  @endif

  {{-- Fabrics: a cloth swatch on pegs (canvas; home-fabrics.js). The tab text and cards are config/kayaa.php home.fabrics (PROPOSED copy, confirm
       with the client); "Found in" links the real products that match. Without scripts the cards are shown one after the other. --}}
  @if($fabrics)
    <section class="section fabrics" id="fabrics" aria-labelledby="fab-title">
      <div class="container fabrics__grid">
        <div>
          <p class="eyebrow">Our fabrics</p>
          <h2 class="split" id="fab-title">Feel it before you buy it</h2>
          <p class="section__intro" data-reveal>Everything that touches your baby's skin is picked for Sri Lankan heat: breathable, soft and gentle. Grab the swatch and give it a tug.</p>
          <div class="fab-tabs" role="tablist" aria-label="Fabrics" id="fabTabs" data-reveal>
            @foreach($fabrics as $i => $f)
              <button class="fab-tab" role="tab" type="button" id="fab-{{ $f['key'] }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="fab-panel-{{ $f['key'] }}" @if($i) tabindex="-1" @endif data-key="{{ $f['key'] }}" data-name="{{ $f['name'] }}" data-col="{{ $f['colour'] }}" data-stiff="{{ $f['stiff'] }}" data-flutter="{{ $f['flutter'] }}"><i aria-hidden="true"></i>{{ $f['name'] }}</button>
            @endforeach
          </div>
          <div class="fab-card" data-reveal>
            <div class="fab-swap" id="fabSwap">
              @foreach($fabrics as $i => $f)
                <div class="fab-panel" role="tabpanel" id="fab-panel-{{ $f['key'] }}" aria-labelledby="fab-{{ $f['key'] }}" @if($i) hidden @endif>
                  <h3 class="fab-panel__name">{{ $f['name'] }}</h3>
                  <p class="fab-panel__line">{{ $f['line'] }}</p>
                  <dl class="fab-facts">
                    <div><dt>Feels</dt><dd>{{ $f['feel'] }}</dd></div>
                    <div><dt>Best for</dt><dd>{{ $f['best'] }}</dd></div>
                    <div><dt>Care</dt><dd>{{ $f['care'] }}</dd></div>
                  </dl>
                  <p class="fab-found">Found in:
                    @forelse($f['products'] as $product)<a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>@if(! $loop->last), @endif
                    @empty<a href="{{ route('search', ['q' => $f['name']]) }}">Search for {{ \Illuminate\Support\Str::lower($f['name']) }}</a>
                    @endforelse
                  </p>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        <div class="cloth-wrap" id="clothWrap" data-reveal>
          <span class="cloth-label" id="clothLabel">{{ $fabrics[0]['name'] }}</span>
          <canvas id="cloth" role="img" aria-label="A {{ \Illuminate\Support\Str::lower($fabrics[0]['name']) }} swatch hanging from pegs, moving in a breeze"></canvas>
          <span class="cloth-hint" id="clothHint"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 11V6a2 2 0 0 1 4 0v5"/><path d="M13 10.5V9a2 2 0 0 1 4 0v3"/><path d="M17 11.5a2 2 0 0 1 4 0V15a6 6 0 0 1-6 6h-2.2a6 6 0 0 1-4.3-1.8L5 16.6a2 2 0 0 1 2.8-2.9L9 15"/></svg>Drag the fabric</span>
        </div>
      </div>
    </section>
  @endif

  {{-- New this week / Bestsellers: one tab list over two lists of the shared product card (the same card and quick-add sheet as Shop and
       Category). New = the products flagged new, newest first; Bestsellers = the products with the admin Featured toggle on. A list with
       nothing in it loses its tab; with one list left there is no tab list at all. --}}
  @if($lists)
    @php $first = array_key_first($lists); $tabbed = count($lists) > 1; @endphp
    <section class="section products" id="products" aria-labelledby="prod-title">
      <div class="container">
        <div class="section__head">
          <div>
            <p class="eyebrow">Fresh on the shelf</p>
            <h2 id="prod-title">{{ $lists[$first]['title'] }}</h2>
          </div>
          @if($tabbed)
            <div class="tabs" role="tablist" aria-label="Product lists" id="prodTabs" data-reveal>
              <span class="tab-pill" id="tabPill" aria-hidden="true"></span>
              @foreach($lists as $key => $list)
                <button class="tab" type="button" role="tab" aria-selected="{{ $key === $first ? 'true' : 'false' }}" aria-controls="panel-{{ $key }}" id="tab-{{ $key }}" data-list="{{ $key }}" data-title="{{ $list['title'] }}" @if($key !== $first) tabindex="-1" @endif>{{ $list['title'] }}</button>
              @endforeach
            </div>
          @endif
        </div>
        @foreach($lists as $key => $list)
          <div class="products__panel" @if($tabbed) role="tabpanel" aria-labelledby="tab-{{ $key }}" @endif id="panel-{{ $key }}" @if($key !== $first) hidden @endif>
            @if($key !== $first)<h3 class="products__panel-title">{{ $list['title'] }}</h3>@endif
            <ul class="product-grid" role="list">
              @foreach($list['products'] as $product)
                <x-store.product-card :product="$product" />
              @endforeach
            </ul>
          </div>
        @endforeach
        <div class="section__foot">
          <a class="btn btn--secondary btn--lg" href="{{ route('shop.index') }}">Shop all</a>
        </div>
      </div>
    </section>
  @endif

  {{-- Promo banners (Admin > Banners, home_promo). Each banner is one card with one real link (the button, stretched over the card). The two
       tints and the two drawn garments alternate as the prototype's pair does; with one live banner it spans the row (resources/css/laravel.css). --}}
  @if($promos->isNotEmpty())
    <section class="section section--flush" aria-label="Promotions">
      <div class="container promos__grid">
        @foreach($promos as $i => $promo)
          <article class="promo promo--{{ $i % 2 === 0 ? 'lilac' : 'sky' }}" data-reveal @if($i % 2) style="--i: 1" @endif>
            <div class="promo__body">
              @if($promo->eyebrow)<p class="eyebrow">{{ $promo->eyebrow }}</p>@endif
              <h2 class="promo__title">{{ $promo->title }}</h2>
              @if($promo->body)<p class="promo__text">{{ $promo->body }}</p>@endif
              @if($promo->cta_url)
                <a class="btn btn--primary btn--sm promo__link" href="{{ $promo->cta_url }}">{{ $promo->cta_label ?: 'Shop now' }}</a>
              @endif
            </div>
            <div class="promo__art" data-art="{{ $i % 2 === 0 ? 'newborn' : 'cardigan' }}" data-c="{{ $i % 2 === 0 ? 'white' : 'peri' }}" data-speed=".06" aria-hidden="true"></div>
          </article>
        @endforeach
      </div>
    </section>
  @endif

  {{-- Reviews: the home reviews feed (approved, verified-purchase, rated 4 or more, the latest three). Left out when there are none.
       On phones the list is a snap scroller with a peek. --}}
  @if($reviews->isNotEmpty())
    <section class="section section--flush reviews-sec" aria-labelledby="rev-title">
      <div class="container">
        <div class="section__head">
          <div>
            <p class="eyebrow">From parents</p>
            <h2 class="split" id="rev-title">Loved in Sri Lankan homes</h2>
          </div>
          <a class="link-arrow" href="{{ route('products.show', $reviews->first()->product) }}#reviews" data-reveal>Read reviews <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
        </div>
        <ul class="reviews" role="list" id="reviews" aria-label="Customer reviews">
          @foreach($reviews as $i => $review)
            @php $firstName = $review->firstName(); $size = $review->sizeBought(); @endphp
            <li>
              <figure class="review" data-reveal @if($i) style="--i: {{ $i }}" @endif>
                <div class="stars" role="img" aria-label="{{ $review->rating }} out of 5 stars">@for($s = 1; $s <= 5; $s++)<svg viewBox="0 0 24 24" @if($s > $review->rating) class="is-off" @endif aria-hidden="true" focusable="false">{!! $star !!}</svg>@endfor</div>
                <blockquote>{{ $review->body }}</blockquote>
                <figcaption><div class="who"><span class="av" aria-hidden="true">{{ mb_strtoupper(mb_substr($firstName, 0, 1)) }}</span><span><b>{{ $firstName }}</b>{{ $review->product->name }}@if($size) · <span class="who__size">Bought {{ $size }}</span>@endif</span></div><span class="vp"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg> Verified purchase</span></figcaption>
              </figure>
            </li>
          @endforeach
        </ul>
      </div>
    </section>
  @endif

  {{-- Size help: the WhatsApp number is the one in Admin > Settings, as in the footer; without one the button goes to the contact page. --}}
  <section class="section section--flush" aria-labelledby="help-title">
    <div class="container">
      <div class="help" data-reveal data-live>
        <div class="help__tape" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24" focusable="false"><circle cx="10" cy="11" r="7"/><circle cx="10" cy="11" r="2"/><path d="M17 11h4v6h-9"/><path d="M14 17v-2M17 17v-2"/></svg></div>
        <div>
          <h2 id="help-title">Not sure which size?</h2>
          <p>Send us your baby's age and weight{{ $waHref ? ' on WhatsApp' : '' }} and we'll suggest a size. If it doesn't fit, swap it free within 14 days.</p>
        </div>
        @if($waHref)
          <a class="btn btn--white btn--lg" href="{{ $waHref }}" target="_blank" rel="noopener"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-whatsapp"></use></svg> Chat on WhatsApp</a>
        @else
          <a class="btn btn--white btn--lg" href="{{ route('pages.contact') }}">Contact us</a>
        @endif
      </div>
    </div>
  </section>

</x-layouts.store>
