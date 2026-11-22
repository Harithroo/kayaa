@props(['navCategories', 'sizeOptions', 'waHref' => null])
{{-- tools/partials/menu-drawer.html --}}
<div class="drawer drawer--left" id="menu-drawer" data-layer="menu" role="dialog" aria-modal="true" aria-labelledby="menu-title" aria-hidden="true">
  <div class="drawer__head">
    <h2 class="drawer__title" id="menu-title"><span class="wordmark">kayaa</span><span class="visually-hidden"> menu</span></h2>
    <button type="button" class="icon-btn icon-btn--plain" data-close-layer aria-label="Close menu">
      <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-x"></use></svg>
    </button>
  </div>
  <div class="drawer__body">
    <nav aria-label="Menu">
      <div class="menu-group">
        <ul role="list">
          <li><a href="{{ route('shop.index') }}" data-nav="shop category product search">Shop</a></li>
          <li><a href="{{ route('categories.index') }}" data-nav="categories">Categories</a></li>
          <li><a href="{{ route('shop.index', ['sort' => 'new']) }}">New in</a></li>
          <li><a href="{{ route('shop.index', ['sale' => 1]) }}">Sale</a></li>
          <li><a href="{{ route('wishlist.index') }}" data-nav="wishlist">Wishlist</a></li>
          <li><a href="{{ route('pages.show', 'about') }}" data-nav="about">Our story</a></li>
        </ul>
      </div>
      {{-- Shop by category and Shop by age are collapsed so the first screen stays short (native details: works without JS). Help lives in the footer. --}}
      <details class="menu-group menu-group--fold">
        <summary class="menu-group__title"><span>Shop by category</span><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
        <ul role="list">
          @foreach($navCategories as $cat)
            <li><a href="{{ $cat->url() }}">{{ $cat->name }} <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-right"></use></svg></a></li>
          @endforeach
        </ul>
      </details>
      @if($sizeOptions->isNotEmpty())
        <details class="menu-group menu-group--fold">
          <summary class="menu-group__title"><span>Shop by age</span><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
          <ul class="menu-group__cols" role="list">
            @foreach($sizeOptions as $size)
              <li><a href="{{ route('shop.index', ['size' => $size->label]) }}">{{ $size->label }}</a></li>
            @endforeach
          </ul>
        </details>
      @endif
      <div class="menu-group">
        <p class="menu-group__title">Account</p>
        <ul role="list">
          @auth
            <li><a href="{{ route('account.index') }}" data-nav="account">My account</a></li>
            <li>
              <form method="post" action="{{ route('account.logout') }}">@csrf
                <button type="submit" class="menu-group__signout">Sign out</button>
              </form>
            </li>
          @else
            <li><a href="{{ route('account.login') }}" data-nav="login" data-account-link><span data-account-label>Sign in / register</span></a></li>
          @endauth
        </ul>
      </div>
    </nav>
  </div>
  @if($waHref)
    <div class="drawer__foot">
      <a class="btn btn--secondary btn--block" href="{{ $waHref }}" target="_blank" rel="noopener">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-message-circle"></use></svg> Chat on WhatsApp
      </a>
    </div>
  @endif
</div>
