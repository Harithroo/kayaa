@props(['title' => null, 'metaDescription' => null, 'category' => null, 'nav' => null])
@php
  $wa = \App\Support\StoreContact::whatsapp();
  $waHref = \App\Support\StoreContact::whatsappHref();
  $tel = \App\Support\StoreContact::phone();
  $telHref = \App\Support\StoreContact::phoneHref();
  $active = $nav ?? (request()->routeIs('home') ? 'home' : (request()->routeIs('shop.*', 'products.*') ? 'shop' : (request()->routeIs('search') ? 'search' : (request()->routeIs('cart.*', 'checkout.*') ? 'cart' : (request()->routeIs('orders.*') ? 'orders' : (request()->routeIs('account.*', 'password.*') ? 'account' : null))))));
  $ico = [
    'home' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 11 12 4l8 7"/><path d="M6 10v10h12V10"/></svg>',
    'shop' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 5h16M4 12h16M4 19h16"/></svg>',
    'search' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
    'cart' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9.2 8V6.2a2.8 2.8 0 0 1 5.6 0V8"/></svg>',
    'account' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8.5" r="3.6"/><path d="M5 20c.9-3.6 3.7-5.4 7-5.4s6.1 1.8 7 5.4"/></svg>',
  ];
  // Drawer menu. Search and cart are left out — both already have their own icon
  // in the header and their own slot in the tab bar.
  $menu = [
    ['home', 'Home', route('home')],
    ['shop', 'Shop', route('shop.index')],
    ['about', 'About Kayaa', route('pages.show', 'about')],
    ['contact', 'Contact & help', route('pages.contact')],
    ['orders', 'Track my order', route('orders.track')],
    ['account', auth()->check() ? 'My account' : 'Sign in / register', auth()->check() ? route('account.index') : route('account.login')],
  ];
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  @if (config('kayaa.noindex'))
  <meta name="robots" content="noindex, nofollow">
  @endif
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>{{ isset($title) ? "$title · Kayaa" : 'Kayaa · Baby clothing, Sri Lanka' }}</title>
  <meta name="description" content="{{ $metaDescription ?? 'Soft cotton baby clothing sized by age, delivered island-wide. Cash on delivery available.' }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Gabarito:wght@500;600;700&family=Hanken+Grotesk:wght@400;500;600&display=swap">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="store">
  @if($topbar)
    <div class="topbar">@if($topbar->cta_url)<a href="{{ $topbar->cta_url }}">{{ $topbar->title }}</a>@else{{ $topbar->title }}@endif</div>
  @endif

  <header class="header">
    <div class="wrap">
      <div class="header-in">
        <button class="iconbtn menubtn" aria-label="Menu" data-open-drawer>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <a class="logo" href="{{ route('home') }}" aria-label="Kayaa home">kaya<span>a</span></a>
        <nav class="desknav" aria-label="Site">
          <a href="{{ route('shop.index') }}" @class(['on' => $active === 'shop'])>Shop</a>
          <a href="{{ route('pages.show', 'about') }}" @class(['on' => request()->is('about')])>About</a>
          <a href="{{ route('pages.contact') }}" @class(['on' => request()->is('contact')])>Contact</a>
          <a href="{{ route('orders.track') }}" @class(['on' => $active === 'orders'])>Track order</a>
        </nav>
        <a class="iconbtn" href="{{ auth()->check() ? route('account.index') : route('account.login') }}" aria-label="{{ auth()->check() ? 'My account' : 'Sign in' }}">
          <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8.5" r="3.6"/><path d="M5 20c.9-3.6 3.7-5.4 7-5.4s6.1 1.8 7 5.4"/></svg>
        </a>
        <a class="iconbtn" href="{{ route('search') }}" aria-label="Search">
          <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        </a>
        <a class="iconbtn" href="{{ route('cart.index') }}" aria-label="Cart, {{ $cartCount }} items" data-open-cart>
          <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9.2 8V6.2a2.8 2.8 0 0 1 5.6 0V8"/></svg>
          <span class="count" @unless($cartCount > 0) hidden @endunless>{{ $cartCount }}</span>
        </a>
      </div>
      <nav class="catnav" aria-label="Categories">
        <a href="{{ route('shop.index') }}" @class(['on' => request()->routeIs('shop.index') && !request('sale')])>New in</a>
        @foreach($navCategories as $cat)
          <a href="{{ $cat->url() }}" @class(['on' => $category?->id === $cat->id])>{{ $cat->name }}</a>
        @endforeach
        <a class="sale" href="{{ route('shop.index', ['sale' => 1]) }}">Sale</a>
      </nav>
    </div>
  </header>

  <div class="drawer" data-drawer hidden>
    <div class="drawer-panel" role="dialog" aria-label="Menu">
      <div class="drawer-head">
        <p class="logo">kaya<span>a</span></p>
        <button class="iconbtn" aria-label="Close" data-close-drawer>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
      </div>
      @foreach($menu as [$key, $label, $href])
        <a class="item {{ $active === $key ? 'on' : '' }}" href="{{ $href }}">{{ $label }}</a>
      @endforeach
      @if($waHref)<a class="wa" href="{{ $waHref }}">WhatsApp us</a>@endif
    </div>
    <div class="drawer-scrim" data-close-drawer></div>
  </div>

  {{-- Cart: slides in from the right on desktop, up from the bottom on mobile. --}}
  <div class="cartdrawer" data-cart-drawer @unless(session('cart_open')) hidden @endunless>
    {{-- Anchors, not buttons: without JS these close the drawer by reloading the page. --}}
    <a class="cartdrawer-scrim" href="{{ url()->current() }}" aria-label="Close cart" data-close-cart></a>
    <aside class="cartdrawer-panel" role="dialog" aria-modal="true" aria-label="Your cart">
      <div class="cartdrawer-head">
        <p class="t">Your cart <span class="muted" data-cart-count-label>{{ $cartCount > 0 ? "($cartCount)" : '' }}</span></p>
        <a class="iconbtn" href="{{ url()->current() }}" aria-label="Close cart" data-close-cart>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </a>
      </div>
      <x-store.cart-panel :cart="$cart" />
    </aside>
  </div>

  @if(session('success') || session('error'))
    <div class="wrap">
      @if(session('success'))<p class="flash ok" role="status">{{ session('success') }}</p>@endif
      @if(session('error'))<p class="flash err" role="alert">{{ session('error') }}</p>@endif
    </div>
  @endif

  <main>
    {{ $slot }}
  </main>

  <footer class="foot">
    <div class="wrap">
      <div class="foot-grid">
        <div class="foot-brand">
          <p class="footlogo">kayaa</p>
          <p class="blurb">Soft, honest clothing made for Sri Lankan weather.</p>
        </div>
        <div class="foot-col"><h3>Shop</h3><ul>
          <li><a href="{{ route('shop.index') }}">New in</a></li>
          @foreach($navCategories->take(3) as $cat)<li><a href="{{ $cat->url() }}">{{ $cat->name }}</a></li>@endforeach
          <li><a href="{{ route('shop.index', ['sale' => 1]) }}">Sale</a></li>
        </ul></div>
        <div class="foot-col"><h3>Help</h3><ul>
          <li><a href="{{ route('pages.size-guide') }}">Size guide</a></li>
          <li><a href="{{ route('pages.show', 'delivery') }}">Delivery</a></li>
          <li><a href="{{ route('pages.show', 'returns') }}">Returns &amp; exchanges</a></li>
          <li><a href="{{ route('orders.track') }}">Track your order</a></li>
          <li><a href="{{ auth()->check() ? route('account.index') : route('account.login') }}">{{ auth()->check() ? 'My account' : 'Sign in / register' }}</a></li>
        </ul></div>
        <div class="foot-col"><h3>Contact</h3><ul>
          @if($wa)<li><a href="{{ $waHref }}">WhatsApp {{ $wa }}</a></li>@endif
          @if($tel)<li><a href="{{ $telHref }}">Call {{ $tel }}</a></li>@endif
          <li><a href="mailto:{{ config('kayaa.email') }}">{{ config('kayaa.email') }}</a></li>
          <li><a href="{{ route('pages.show', 'about') }}">About Kayaa</a></li>
          <li><a href="{{ route('pages.show', 'privacy') }}">Privacy</a> · <a href="{{ route('pages.show', 'terms') }}">Terms</a></li>
        </ul></div>
      </div>
      <p class="fine">© {{ date('Y') }} Kayaa · Colombo, Sri Lanka · Payments secured by PayHere</p>
    </div>
  </footer>

  <nav class="tabbar" aria-label="Main">
    <div class="tabbar-in">
      @foreach([['home', 'Home', route('home')], ['shop', 'Shop', route('shop.index')], ['search', 'Search', route('search')], ['cart', 'Cart', route('cart.index')], ['account', auth()->check() ? 'Account' : 'Sign in', auth()->check() ? route('account.index') : route('account.login')]] as [$key, $label, $href])
        <a class="tab {{ $active === $key ? 'on' : '' }}" href="{{ $href }}" @if($key === 'cart') data-open-cart @endif>
          <span class="ico">{!! $ico[$key] !!}@if($key === 'cart')<span class="count" @unless($cartCount > 0) hidden @endunless>{{ $cartCount }}</span>@endif</span>
          <span>{{ $label }}</span>
        </a>
      @endforeach
    </div>
  </nav>
</div>
</body>
</html>
