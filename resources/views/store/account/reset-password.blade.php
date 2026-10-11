{{-- Set a new password, from the prototype's html/account/reset-password.html. --}}
<x-layouts.store title="Set a new password" page="account login">
  <div class="container auth" data-auth-page="reset">
    <div class="auth-card">
      <h1>Set a new password</h1>
      <p class="auth-card__lede">Choose a new password for your account.</p>

      @if($errors->any())
        <div class="alert alert--error" role="alert" tabindex="-1" id="reset-summary">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
          <div><p class="alert__title">Please check your details</p><ul class="alert__list">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        </div>
      @endif

      <form class="auth-form" method="post" action="{{ route('password.update') }}" novalidate data-auth-form data-summary="reset-summary">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field" data-field>
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="username" inputmode="email" value="{{ old('email', $email ?? '') }}"
                 data-validate="email" data-required-message="Enter your email address" data-invalid-message="Enter a valid email address, for example name@example.com">
          <p class="field__error" id="email-error" hidden></p>
        </div>
        <x-store.password-field id="password" name="password" label="New password" autocomplete="new-password" required-message="Choose a new password" hint="At least 8 characters." />
        <x-store.password-field id="password_confirmation" name="password_confirmation" label="Confirm new password" autocomplete="new-password" required-message="Repeat your new password" />
        <button type="submit" class="btn btn--primary btn--lg btn--block">Save new password</button>
      </form>

      <p class="auth-switch">Back to <a class="auth-link" href="{{ route('account.login') }}">Sign in</a></p>
    </div>
  </div>
</x-layouts.store>
