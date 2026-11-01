{{-- tools/partials/footer-minimal.html --}}
<footer class="site-footer site-footer--minimal">
  <div class="container footer-min">
    <nav aria-label="Help and policies">
      <ul class="footer-min__links" role="list">
        <li><a href="{{ route('pages.show', 'delivery') }}">Delivery</a></li>
        <li><a href="{{ route('pages.show', 'returns') }}">Returns</a></li>
        <li><a href="{{ route('pages.show', 'privacy') }}">Privacy</a></li>
        <li><a href="{{ route('pages.show', 'terms') }}">Terms</a></li>
        <li><a href="{{ route('pages.contact') }}">Contact</a></li>
      </ul>
    </nav>
    <p class="footer-min__copy">&copy; {{ date('Y') }} Kayaa</p>
  </div>
</footer>
