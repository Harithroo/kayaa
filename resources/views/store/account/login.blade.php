{{-- Sign in, from the prototype's html/account/login.html. --}}
<x-layouts.store title="Sign in" page="account login">
  <div class="container auth" data-auth-page="login">
    <div class="auth-card">
      <h1>Sign in</h1>
      <p class="auth-card__lede">Welcome back. Sign in to see your orders and reviews.</p>

      @if($errors->any())
        <div class="alert alert--error" role="alert" tabindex="-1" id="login-summary">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
          <div><p class="alert__title">Please check your details</p><ul class="alert__list">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        </div>
      @endif
      @if(session('status'))
        <div class="alert alert--success" role="status" tabindex="-1">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg>
          <div><p>{{ session('status') }}</p></div>
        </div>
      @endif

      <form class="auth-form" method="post" action="{{ route('account.login') }}" novalidate data-auth-form data-summary="login-summary">
        @csrf
        <div class="field" data-field>
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="username" inputmode="email" value="{{ old('email') }}"
                 data-validate="email" data-required-message="Enter your email address" data-invalid-message="Enter a valid email address, for example name@example.com">
          <p class="field__error" id="email-error" hidden></p>
        </div>
        <x-store.password-field id="password" name="password" label="Password" autocomplete="current-password" required-message="Enter your password" />
        <div class="auth-row">
          <label class="checkbox">
            <input type="checkbox" name="remember" value="1">
            <span class="checkbox__box" aria-hidden="true"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg></span>
            <span>Keep me signed in</span>
          </label>
          <a class="auth-link" href="{{ route('password.request') }}">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn--primary btn--lg btn--block">Sign in</button>
      </form>

      <p class="auth-switch">New here? <a class="auth-link" href="{{ route('account.register') }}">Create an account</a></p>
      <div class="auth-guest">
        <p>No account needed to order. You can check out as a guest and track an order any time.</p>
        <p class="auth-guest__links"><a class="auth-link" href="{{ route('shop.index') }}">Shop as a guest</a><a class="auth-link" href="{{ route('orders.track') }}">Track an order</a></p>
      </div>
    </div>
  </div>
</x-layouts.store>
