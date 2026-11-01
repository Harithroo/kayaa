@props(['navCategories', 'sizeOptions', 'waHref' => null, 'wa' => null, 'tel' => null, 'telHref' => null])
{{-- tools/partials/footer.html, including the decorative art zone (waves, swaying
     clothesline, giant wordmark). The clothesline is inlined so the page CSS can
     animate the garments; app.js only runs it while it is on screen. --}}
<footer class="site-footer">
  <div class="container">
    <div class="footer__grid">
      <div class="footer__brand">
        <a class="wordmark" href="{{ route('home') }}" aria-label="Kayaa home">kayaa</a>
        <p class="footer__blurb">Soft, honest clothing made for Sri Lankan weather.</p>
        <nav aria-label="Social media">
          <ul class="footer__social" role="list">
            {{-- TODO: real social URLs from the client (then add target="_blank" rel="noopener noreferrer") --}}
            <li><a class="footer__social-link" href="#" aria-label="Instagram"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-instagram"></use></svg></a></li>
            <li><a class="footer__social-link" href="#" aria-label="Facebook"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-facebook"></use></svg></a></li>
            <li><a class="footer__social-link" href="#" aria-label="TikTok"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-tiktok"></use></svg></a></li>
            @if($waHref)
              <li><a class="footer__social-link" href="{{ $waHref }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-whatsapp"></use></svg></a></li>
            @endif
          </ul>
        </nav>
        <ul class="footer__contact" role="list">
          @if($waHref)
            <li><a href="{{ $waHref }}" target="_blank" rel="noopener"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-message-circle"></use></svg> Chat on WhatsApp</a></li>
          @endif
          @if($tel)<li><a href="{{ $telHref }}">{{ $tel }}</a></li>@endif
          <li><a href="mailto:{{ config('kayaa.email') }}">{{ config('kayaa.email') }}</a></li>
        </ul>
      </div>
      @if($sizeOptions->isNotEmpty())
        <nav aria-labelledby="footer-age">
          <h2 class="footer__title" id="footer-age">Shop by size</h2>
          <ul class="footer__list footer__list--cols" role="list">
            @foreach($sizeOptions as $size)
              <li><a href="{{ route('shop.index', ['size' => $size->label]) }}">{{ $size->label }}</a></li>
            @endforeach
          </ul>
        </nav>
      @endif
      <nav aria-labelledby="footer-help">
        <h2 class="footer__title" id="footer-help">Help</h2>
        <ul class="footer__list" role="list">
          <li><a href="{{ route('orders.track') }}">Track order</a></li>
          <li><a href="{{ route('wishlist.index') }}">Wishlist</a></li>
          <li><a href="{{ route('pages.size-guide') }}">Size guide</a></li>
          <li><a href="{{ route('pages.show', 'delivery') }}">Delivery</a></li>
          <li><a href="{{ route('pages.show', 'returns') }}">Returns</a></li>
          <li><a href="{{ route('pages.contact') }}">Contact</a></li>
        </ul>
      </nav>
      <nav aria-labelledby="footer-company">
        <h2 class="footer__title" id="footer-company">Company</h2>
        <ul class="footer__list" role="list">
          <li><a href="{{ route('categories.index') }}">All categories</a></li>
          <li><a href="{{ route('pages.show', 'about') }}">Our story</a></li>
          <li><a href="{{ route('pages.show', 'privacy') }}">Privacy policy</a></li>
          <li><a href="{{ route('pages.show', 'terms') }}">Terms &amp; conditions</a></li>
        </ul>
      </nav>
    </div>
    <div class="footer__legal">
      <p>&copy; {{ date('Y') }} Kayaa. Colombo, Sri Lanka</p>
      <p>Secure card payments &middot; Island-wide delivery</p>
    </div>
  </div>
  <div class="footer__art" aria-hidden="true" data-footer-art>
    <img class="footer__waves" src="{{ asset('assets/img/footer/footer-waves.svg') }}" alt="" width="1440" height="260" loading="lazy" decoding="async">
    <div class="footer__line">
      <x-store.shell.clothesline />
    </div>
    <div class="container">
      <img class="footer__wordmark" src="{{ asset('assets/img/footer/wordmark.svg') }}" alt="" width="1000" height="364" loading="lazy" decoding="async">
    </div>
  </div>
</footer>
