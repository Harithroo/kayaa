@props(['back' => null, 'backLabel' => null])
{{-- tools/partials/header-minimal.html: checkout and thank-you. --}}
<header class="site-header site-header--minimal">
  <div class="container site-header__bar site-header__bar--minimal">
    <a class="wordmark" href="{{ route('home') }}" aria-label="Kayaa home">kayaa</a>
    <p class="secure-label"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-lock"></use></svg> Secure checkout</p>
    @if($back)
      <a class="back-link site-header__back" href="{{ $back }}" data-back-link><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-left"></use></svg> <span data-back-label>{{ $backLabel ?? 'Back to bag' }}</span></a>
    @endif
  </div>
</header>
