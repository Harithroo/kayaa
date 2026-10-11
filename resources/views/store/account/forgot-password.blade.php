{{-- Forgot password, from the prototype's html/account/forgot-password.html. --}}
<x-layouts.store title="Forgot password" page="account login">
  <div class="container auth" data-auth-page="forgot">
    <div class="auth-card">
      <h1>Forgot your password?</h1>
      <p class="auth-card__lede">Enter your email and we'll send you a link to set a new one.</p>

      @if($errors->any())
        <div class="alert alert--error" role="alert" tabindex="-1" id="forgot-summary">
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

      <form class="auth-form" method="post" action="{{ route('password.email') }}" novalidate data-auth-form data-summary="forgot-summary">
        @csrf
        <div class="field" data-field>
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="username" inputmode="email" value="{{ old('email') }}"
                 data-validate="email" data-required-message="Enter your email address" data-invalid-message="Enter a valid email address, for example name@example.com">
          <p class="field__error" id="email-error" hidden></p>
        </div>
        <button type="submit" class="btn btn--primary btn--lg btn--block">Send reset link</button>
      </form>

      <p class="auth-switch">Remembered it? <a class="auth-link" href="{{ route('account.login') }}">Sign in</a></p>
    </div>
  </div>
</x-layouts.store>
