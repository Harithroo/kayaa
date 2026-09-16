@props(['title' => null, 'metaDescription' => null, 'category' => null])
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
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
  <div class="topbar">Island-wide delivery in 2–4 days · Free over {{ money(config('kayaa.free_shipping_over')) }}</div>

  <header class="header">
    <div class="wrap">
      <div class="header-in">
        <a class="logo" href="{{ route('home') }}" aria-label="Kayaa home">kaya<span>a</span></a>
        <a class="iconbtn" href="{{ route('orders.track') }}" aria-label="Track your order">
          <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg>
        </a>
        <a class="iconbtn" href="{{ route('cart.index') }}" aria-label="Cart, {{ $cartCount }} items">
          <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9.2 8V6.2a2.8 2.8 0 0 1 5.6 0V8"/></svg>
          @if($cartCount > 0)<span class="cartdot">{{ $cartCount }}</span>@endif
        </a>
      </div>
      <nav class="nav" aria-label="Categories">
        <a href="{{ route('shop.index') }}" @class(['on' => request()->routeIs('shop.index') && !request('sale')])>New in</a>
        @foreach($navCategories as $cat)
          <a href="{{ $cat->url() }}" @class(['on' => $category?->id === $cat->id])>{{ $cat->name }}</a>
        @endforeach
        <a class="sale" href="{{ route('shop.index', ['sale' => 1]) }}">Sale</a>
      </nav>
    </div>
  </header>

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
        <div>
          <p class="footlogo">kayaa</p>
          <p style="margin:0 0 24px;max-width:30ch;color:#9FB2A4;font-size:13.5px">Soft, honest clothing made for Sri Lankan weather.</p>
        </div>
        <div><h3>Shop</h3><ul>
          @foreach($navCategories->take(4) as $cat)<li><a href="{{ $cat->url() }}">{{ $cat->name }}</a></li>@endforeach
          <li><a href="{{ route('shop.index', ['sale' => 1]) }}">Sale</a></li>
        </ul></div>
        <div><h3>Help</h3><ul>
          <li><a href="{{ route('pages.size-guide') }}">Size guide</a></li>
          <li><a href="{{ route('pages.show', 'delivery') }}">Delivery</a></li>
          <li><a href="{{ route('pages.show', 'returns') }}">Returns &amp; exchanges</a></li>
          <li><a href="{{ route('orders.track') }}">Track your order</a></li>
        </ul></div>
        <div><h3>Contact</h3><ul>
          @if(config('kayaa.whatsapp'))<li><a href="https://wa.me/94{{ preg_replace('/\D+/', '', ltrim(config('kayaa.whatsapp'), '0')) }}">WhatsApp {{ config('kayaa.whatsapp') }}</a></li>@endif
          <li><a href="mailto:{{ config('kayaa.email') }}">{{ config('kayaa.email') }}</a></li>
          <li><a href="{{ route('pages.show', 'about') }}">About Kayaa</a></li>
          <li><a href="{{ route('pages.show', 'privacy') }}">Privacy</a> · <a href="{{ route('pages.show', 'terms') }}">Terms</a></li>
        </ul></div>
      </div>
      <p class="fine">© {{ date('Y') }} Kayaa · Colombo, Sri Lanka · Cash on delivery island-wide</p>
    </div>
  </footer>
</div>
</body>
</html>
