{{--
  Storefront shell, ported from the `frontend` prototype (tools/page-skeleton.html plus
  tools/partials/*). Three variants, chosen with the `shell` prop:

    full     — announcement, header with mega menu, category row, drawers, quick add,
               footer with the art zone, mobile tab bar. Every browsing page.
    minimal  — wordmark, "Secure checkout" and a back link; short footer. Checkout and
               thank-you, where nothing should pull the customer out of the flow.
    error    — wordmark only, short footer.

  `page` is what the prototype wrote as <body data-page="...">: space-separated tokens
  that app.js matches against every [data-nav] link to set aria-current.
--}}
@props([
  'title' => null,
  'metaDescription' => null,
  'page' => null,
  'shell' => 'full',
  'back' => null,
  'backLabel' => null,
  'canonical' => null,
  'bodyClass' => null,
])
@php
  $wa = \App\Support\StoreContact::whatsapp();
  $waHref = \App\Support\StoreContact::whatsappHref();
  $tel = \App\Support\StoreContact::phone();
  $telHref = \App\Support\StoreContact::phoneHref();
  $pageTitle = $title ? "$title | Kayaa" : 'Kayaa: Baby Clothes in Sri Lanka, Sized by Age';
  $description = $metaDescription ?? 'Soft cotton baby clothing from Newborn to 3Y, with a clear size guide. Secure card payment and island-wide delivery across Sri Lanka.';
  $freeOver = (int) config('kayaa.free_shipping_over');
  // Island-wide window, written from config/kayaa.php rather than typed into the page.
  $days = (array) config('kayaa.delivery_days.default', [3, 5]);
  $dayMin = (int) ($days[0] ?? 3);
  $dayMax = (int) ($days[1] ?? $dayMin);
  $islandDays = $dayMin === $dayMax ? (string) $dayMax : $dayMin.'–'.$dayMax;
  // What the ported JS reads off window.KAYAA_CONFIG. The prototype generated this
  // file from tools/site-config.json; here it is the real store configuration.
  $jsConfig = [
    'currency_prefix' => 'Rs. ',
    'shipping_fee' => (int) (config('kayaa.shipping_fee') / 100),
    'free_shipping_over' => (int) ($freeOver / 100),
    'low_stock_threshold' => (int) config('kayaa.low_stock_threshold'),
    'pay_button_label' => 'Pay now',
    'cod_button_label' => 'Place order',
    'popular_searches' => $popularSearches ?? [],
  ];
  $jsCatalogue = [
    'sizes' => ($navSizes ?? collect())->map(fn ($s) => ['slug' => (string) $s->id, 'label' => $s->label])->values(),
    'colours' => ($navColors ?? collect())->mapWithKeys(fn ($c) => [(string) $c->id => $c->name]),
  ];
  $jsSwatches = ($navColors ?? collect())->filter(fn ($c) => (bool) $c->hex)->mapWithKeys(fn ($c) => [(string) $c->id => $c->hex]);
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light">
  <meta name="theme-color" content="#FAF7FB">
  <link rel="icon" href="data:,">{{-- TODO: real favicon once the logo exists --}}

  <title>{{ $pageTitle }}</title>
  <meta name="description" content="{{ $description }}">
  <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
  @if (config('kayaa.noindex'))
    <meta name="robots" content="noindex, nofollow">
  @endif
  <meta property="og:title" content="{{ $pageTitle }}">
  <meta property="og:description" content="{{ $description }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:site_name" content="Kayaa">
  <meta property="og:locale" content="en_LK">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="{{ $pageTitle }}">
  <meta name="twitter:description" content="{{ $description }}">

  {{-- The two faces used above the fold. Vite hashes them, so the URLs come from the manifest. --}}
  <link rel="preload" href="{{ Vite::asset('resources/fonts/urbanist-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="{{ Vite::asset('resources/fonts/public-sans-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>

  {{-- The prototype generated these three from tools/*.json. Here they are the real
       records: the size and colour labels the quick-add sheet needs, and the swatch
       hexes the admin maintains. --}}
  <script>
    window.KAYAA_CONFIG = @json($jsConfig);
    window.KAYAA_CATALOGUE = @json($jsCatalogue);
    window.KAYAA_SWATCHES = @json($jsSwatches);
  </script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('head')
</head>
<body @class([$bodyClass]) @if($page) data-page="{{ $page }}" @endif @if(session('cart_open')) data-cart-open @endif>
<x-store.icons />

@if($shell === 'full')
  <x-store.shell.announcement :topbar="$topbar" :free-over="$freeOver" :island-days="$islandDays" />
  <x-store.shell.header :nav-categories="$navCategories" :size-options="$navSizes ?? collect()" :cart-count="$cartCount" :wish-count="$wishCount" />
@elseif($shell === 'minimal')
  <x-store.shell.header-minimal :back="$back" :back-label="$backLabel" />
@else
  <x-store.shell.header-error />
@endif

<main id="main">
  @if(session('success') || session('error'))
    <div class="container">
      @if(session('success'))
        <div class="alert alert--success" role="status" tabindex="-1">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg>
          <p>{{ session('success') }}</p>
        </div>
      @endif
      @if(session('error'))
        <div class="alert alert--error" role="alert" tabindex="-1">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
          <p>{{ session('error') }}</p>
        </div>
      @endif
    </div>
  @endif

  {{ $slot }}
</main>

@if($shell === 'full')
  <x-store.shell.footer :nav-categories="$navCategories" :size-options="$navSizes ?? collect()" :wa-href="$waHref" :wa="$wa" :tel="$tel" :tel-href="$telHref" />
  <x-store.shell.menu-drawer :nav-categories="$navCategories" :size-options="$navSizes ?? collect()" :wa-href="$waHref" />
  <x-store.shell.cart-drawer :cart="$cart" :cart-count="$cartCount" />
  <x-store.shell.quick-add />
  <x-store.shell.tabbar :cart-count="$cartCount" />
  <div class="overlay" data-overlay data-close-layer></div>
@else
  <x-store.shell.footer-minimal />
@endif
</body>
</html>
