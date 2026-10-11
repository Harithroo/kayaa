@props(['navCategories', 'sizeOptions', 'cartCount' => 0, 'wishCount' => 0])
{{-- tools/partials/header.html. The split Shop control: the link goes to the shop,
     the chevron opens the mega menu (app.js owns the behaviour). --}}
<div class="mega-scrim" data-mega-scrim></div>
<header class="site-header">
  <div class="container site-header__bar" data-header-bar>
    <button type="button" class="icon-btn icon-btn--plain site-header__menu" data-open-layer="menu" aria-label="Open menu" aria-haspopup="dialog" aria-expanded="false" aria-controls="menu-drawer">
      <svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-menu"></use></svg>
    </button>

    <a class="wordmark" href="{{ route('home') }}" aria-label="Kayaa home">kayaa</a>{{-- TODO: replace with the logo when ready --}}

    <nav class="primary-nav" aria-label="Primary">
      <ul>
        <li class="primary-nav__split" data-mega>
          <a class="primary-nav__link" href="{{ route('shop.index') }}" data-nav="shop category product search">Shop</a>
          <button type="button" class="primary-nav__chevron" data-mega-toggle aria-expanded="false" aria-controls="mega-shop" aria-label="Shop menu">
            <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg>
          </button>
          <div class="mega" id="mega-shop" data-mega-panel inert>
            <div class="mega__panel">
              <div class="mega__main">
                @if($sizeOptions->isNotEmpty())
                  <p class="eyebrow">Shop by size</p>
                  <ul class="chip-list mega__sizes" role="list">
                    @foreach($sizeOptions as $size)
                      <li><a class="chip" href="{{ route('shop.index', ['size' => $size->label]) }}">{{ $size->label }}</a></li>
                    @endforeach
                  </ul>
                @endif
                <p class="eyebrow mega__eyebrow">Browse by category</p>
                <ul class="chip-list" role="list">
                  @foreach($navCategories as $cat)
                    <li><a class="chip" href="{{ $cat->url() }}">{{ $cat->name }}</a></li>
                  @endforeach
                </ul>
              </div>
              <aside class="mega__feature" aria-label="Featured">
                <div class="media" data-placeholder style="--tone: var(--tone-3)"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-image"></use></svg></div>
                <div class="mega__feature-body">
                  <p class="eyebrow">New this week</p>
                  <p class="mega__feature-title">Fresh styles just landed</p>
                  <p class="mega__feature-text">New arrivals, sized by age from newborn upwards.</p>
                  <a class="link-arrow" href="{{ route('shop.index', ['sort' => 'new']) }}">Shop new in <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
                </div>
              </aside>
            </div>
          </div>
        </li>
        <li><a class="primary-nav__link" href="{{ route('categories.index') }}" data-nav="categories">Categories</a></li>
        <li><a class="primary-nav__link" href="{{ route('shop.index', ['sort' => 'new']) }}">New in</a></li>
        <li><a class="primary-nav__link" href="{{ route('shop.index', ['sale' => 1]) }}">Sale</a></li>
        <li><a class="primary-nav__link" href="{{ route('pages.size-guide') }}" data-nav="size-guide">Size guide</a></li>
        <li><a class="primary-nav__link" href="{{ route('pages.show', 'about') }}" data-nav="about">Our story</a></li>
      </ul>
    </nav>

    <div class="site-header__actions">
      <form class="header-search" role="search" aria-label="Site search" action="{{ route('search') }}" method="get" data-header-search data-search="header">
        <button type="button" class="icon-btn header-search__toggle" data-search-toggle aria-expanded="false" aria-controls="header-search-field" aria-label="Search">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
        </button>
        <div class="header-search__field" id="header-search-field">
          <label class="visually-hidden" for="header-q">Search Kayaa</label>
          <input id="header-q" type="search" name="q" placeholder="Search" autocomplete="off">
          <button type="submit" class="header-search__submit" aria-label="Search">
            <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
          </button>
        </div>
      </form>
      <a class="icon-btn site-header__wishlist" href="{{ route('wishlist.index') }}" data-nav="wishlist" aria-label="Wishlist{{ $wishCount ? ", $wishCount saved" : '' }}">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-heart"></use></svg>
        <span class="icon-btn__count" data-wish-count aria-hidden="true" @unless($wishCount > 0) hidden @endunless>{{ $wishCount }}</span>
      </a>
      <a class="icon-btn site-header__account" href="{{ auth()->check() ? route('account.index') : route('account.login') }}" data-nav="account" data-account-link aria-label="{{ auth()->check() ? 'My account' : 'Sign in' }}">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-user"></use></svg>
      </a>
      <button type="button" class="icon-btn" data-open-layer="cart" data-cart-aria aria-label="Open bag, {{ $cartCount }} items" aria-haspopup="dialog">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-shopping-bag"></use></svg>
        <span class="icon-btn__count" data-cart-count aria-hidden="true" @unless($cartCount > 0) hidden @endunless>{{ $cartCount }}</span>
      </button>
    </div>
  </div>

  <nav class="category-row" aria-label="Categories">
    <div class="container">
      <ul>
        @foreach($navCategories as $cat)
          <li><a href="{{ $cat->url() }}">{{ $cat->name }}</a></li>
        @endforeach
      </ul>
    </div>
  </nav>
</header>
