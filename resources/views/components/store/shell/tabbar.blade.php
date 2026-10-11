@props(['cartCount' => 0])
{{-- tools/partials/tabbar.html. Shown under 900px; the cart tab opens the drawer. --}}
<nav class="tabbar" aria-label="Mobile">
  <ul>
    <li><a class="tabbar__item" href="{{ route('home') }}" data-nav="home"><span class="tabbar__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-home"></use></svg></span><span>Home</span></a></li>
    <li><a class="tabbar__item" href="{{ route('shop.index') }}" data-nav="shop category product"><span class="tabbar__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-layout-grid"></use></svg></span><span>Shop</span></a></li>
    <li><a class="tabbar__item" href="{{ route('search', ['focus' => 1]) }}" data-nav="search"><span class="tabbar__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg></span><span>Search</span></a></li>
    <li>
      <button type="button" class="tabbar__item" data-open-layer="cart" data-nav="cart" aria-haspopup="dialog">
        <span class="tabbar__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-shopping-bag"></use></svg><span class="icon-btn__count" data-cart-count aria-hidden="true" @unless($cartCount > 0) hidden @endunless>{{ $cartCount }}</span></span>
        <span>Bag</span>
      </button>
    </li>
    <li><a class="tabbar__item" href="{{ auth()->check() ? route('account.index') : route('account.login') }}" data-nav="account login"><span class="tabbar__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-user"></use></svg></span><span>{{ auth()->check() ? 'Account' : 'Sign in' }}</span></a></li>
  </ul>
</nav>
