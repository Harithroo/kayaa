{{-- Profile, from the prototype's html/account/profile.html. Two independent
     forms: details (PATCH) and password (PATCH), each with its own messages. --}}
<x-layouts.store title="Profile" page="account profile">
  <div class="container account">
    <header class="account__head">
      <h1>Profile</h1>
      <p class="account__lede">Your details and password.</p>
    </header>
    <div class="account__layout">
      <x-store.account-nav active="profile" />
      <div class="account__content">
        <div class="profile-grid" data-profile-page>
          <section class="summary-card profile-card" aria-labelledby="pf-details">
            <h2 id="pf-details">Your details</h2>
            @php $detailErrors = collect(['name', 'email', 'phone'])->flatMap(fn ($f) => $errors->get($f)); @endphp
            @if($detailErrors->isNotEmpty())
              <div class="alert alert--error" role="alert" tabindex="-1" id="details-summary">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
                <div><p class="alert__title">Please check your details</p><ul class="alert__list">@foreach($detailErrors as $e)<li>{{ $e }}</li>@endforeach</ul></div>
              </div>
            @endif
            <form class="profile-form profile-form--2" id="details-form" method="post" action="{{ route('account.profile.update') }}" novalidate data-auth-form data-summary="details-summary">
              @csrf @method('PATCH')
              <div class="field field--wide" data-field>
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name', $user->name) }}" data-required-message="Enter your name">
                <p class="field__error" id="name-error" hidden></p>
              </div>
              <div class="field" data-field>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required autocomplete="email" inputmode="email" value="{{ old('email', $user->email) }}"
                       data-validate="email" data-required-message="Enter your email address" data-invalid-message="Enter a valid email address, for example name@example.com">
                <p class="field__error" id="email-error" hidden></p>
              </div>
              <div class="field" data-field>
                <label for="phone">Mobile number</label>
                <input id="phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" value="{{ old('phone', $user->phone) }}"
                       data-validate="phone" data-invalid-message="Enter a valid Sri Lankan mobile number, for example 071 234 5678">
                <p class="field__error" id="phone-error" hidden></p>
              </div>
              <div class="profile-form__actions"><button type="submit" class="btn btn--primary">Save changes</button></div>
            </form>
          </section>

          <section class="summary-card profile-card" aria-labelledby="pf-password">
            <h2 id="pf-password">Change password</h2>
            @php $passwordErrors = collect(['current_password', 'password'])->flatMap(fn ($f) => $errors->get($f)); @endphp
            @if($passwordErrors->isNotEmpty())
              <div class="alert alert--error" role="alert" tabindex="-1" id="password-summary">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
                <div><p class="alert__title">Please check your details</p><ul class="alert__list">@foreach($passwordErrors as $e)<li>{{ $e }}</li>@endforeach</ul></div>
              </div>
            @endif
            <form class="profile-form" id="password-form" method="post" action="{{ route('account.password.update') }}" novalidate data-auth-form data-summary="password-summary">
              @csrf @method('PATCH')
              <x-store.password-field id="current_password" name="current_password" label="Current password" autocomplete="current-password" required-message="Enter your current password" />
              <x-store.password-field id="password" name="password" label="New password" autocomplete="new-password" required-message="Enter a new password" hint="At least 8 characters." />
              <x-store.password-field id="password_confirmation" name="password_confirmation" label="Confirm new password" autocomplete="new-password" required-message="Repeat your new password" />
              <div class="profile-form__actions"><button type="submit" class="btn btn--primary">Change password</button></div>
            </form>
          </section>
        </div>
      </div>
    </div>
  </div>
</x-layouts.store>
