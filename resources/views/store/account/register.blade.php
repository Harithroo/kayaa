{{-- Create an account, from the prototype's html/account/register.html. --}}
<x-layouts.store title="Create an account" page="account register">
  <div class="container auth" data-auth-page="register">
    <div class="auth-card">
      <h1>Create an account</h1>
      <p class="auth-card__lede">Keep your orders, addresses and reviews in one place.</p>

      @if($errors->any())
        <div class="alert alert--error" role="alert" tabindex="-1" id="register-summary">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
          <div><p class="alert__title">Please check your details</p><ul class="alert__list">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        </div>
      @endif

      <form class="auth-form" method="post" action="{{ route('account.register') }}" novalidate data-auth-form data-summary="register-summary">
        @csrf
        <div class="field" data-field>
          <label for="name">Full name</label>
          <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}" data-required-message="Enter your name">
          <p class="field__error" id="name-error" hidden></p>
        </div>
        <div class="field" data-field>
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="username" inputmode="email" value="{{ old('email') }}"
                 data-validate="email" data-required-message="Enter your email address" data-invalid-message="Enter a valid email address, for example name@example.com">
          <p class="field__error" id="email-error" hidden></p>
        </div>
        <div class="field" data-field>
          <label for="phone">Mobile number (optional)</label>
          <input id="phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" value="{{ old('phone') }}"
                 data-validate="phone" data-invalid-message="Enter a valid Sri Lankan mobile number, for example 071 234 5678">
          <p class="field__error" id="phone-error" hidden></p>
        </div>
        <x-store.password-field id="password" name="password" label="Password" autocomplete="new-password" required-message="Choose a password" hint="At least 8 characters." />
        <x-store.password-field id="password_confirmation" name="password_confirmation" label="Confirm password" autocomplete="new-password" required-message="Repeat your password" />
        <p class="terms">By creating an account you agree to our <a href="{{ route('pages.show', 'terms') }}">Terms</a> and <a href="{{ route('pages.show', 'privacy') }}">Privacy policy</a>.</p>
        <button type="submit" class="btn btn--primary btn--lg btn--block">Create an account</button>
      </form>

      <p class="auth-switch">Already have an account? <a class="auth-link" href="{{ route('account.login') }}">Sign in</a></p>
      <div class="auth-guest">
        <p>No account needed to order. You can check out as a guest and track an order any time.</p>
        <p class="auth-guest__links"><a class="auth-link" href="{{ route('shop.index') }}">Shop as a guest</a><a class="auth-link" href="{{ route('orders.track') }}">Track an order</a></p>
      </div>
    </div>
  </div>
</x-layouts.store>
