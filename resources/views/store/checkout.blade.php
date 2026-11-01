{{-- Checkout, from the prototype's html/checkout.html, on the minimal shell.

     Field names are the server's (first_name/last_name, address, note,
     payment_method onepay|cod), so the existing CheckoutController is unchanged.
     That is also why the prototype's single "Full name" is two fields here: the
     order stores them separately. checkout.js does the client-side validation,
     the delivery estimate and the pay-button label; the server repeats it all. --}}
@php
  $lines = $cart->lines();
  $count = $cart->count();
  $shipping = $cart->shipping();
  // district -> {min, max} working days, keyed by the select's own values.
  $eta = collect(config('kayaa.districts'))->mapWithKeys(function ($d) use ($deliveryDays) {
    $days = $deliveryDays[$d] ?? $deliveryDays['default'];
    return [$d => ['min' => $days[0], 'max' => $days[1]]];
  });
@endphp
<x-layouts.store title="Checkout" page="checkout" shell="minimal" :back="route('cart.index')" back-label="Back to bag">
  <div class="container checkout" data-checkout-page>
    <h1 class="checkout__title">Checkout</h1>

    <div class="checkout__alerts">
      @if($errors->any())
        <div class="alert alert--error" role="alert" tabindex="-1">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
          <div>
            <p class="alert__title">Please check your details</p>
            <ul class="alert__list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
          </div>
        </div>
      @endif
      <div class="alert alert--error" role="alert" tabindex="-1" data-error-summary hidden>
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
        <div><p class="alert__title">Please check your details</p><ul class="alert__list" data-error-list></ul></div>
      </div>
    </div>

    <div class="checkout-layout" data-checkout-filled>
      <div class="checkout-main">
        <details class="checkout-summary-m">
          <summary><span data-summary-heading>Order summary · {{ $count }} {{ Str::plural('item', $count) }} · {{ money($cart->total()) }}</span><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
          <div class="checkout-summary-m__body">
            <x-store.order-lines :lines="$lines" :cart="$cart" />
          </div>
        </details>

        <form id="checkout-form" method="post" action="{{ route('checkout.store') }}" novalidate data-checkout-form>
          @csrf

          <section class="checkout-card" aria-labelledby="co-contact">
            <h2 class="checkout-card__title" id="co-contact"><span class="checkout-card__num" aria-hidden="true">1</span> Contact</h2>
            @guest
              <p class="checkout-card__aside" data-account-aside>Have an account? <a href="{{ route('account.login') }}">Sign in</a></p>
            @endguest
            <div class="checkout-fields">
              <div class="field" data-field>
                <label for="email">Email (optional)</label>
                <input id="email" name="email" type="email" inputmode="email" autocomplete="email" value="{{ old('email', $customer?->email) }}"
                       data-validate="email" aria-describedby="email-hint" data-invalid-message="Enter a valid email address, for example name@example.com">
                <p class="field__hint" id="email-hint">If you create an account later with this email, you'll find this order there.</p>
                <p class="field__error" id="email-error" hidden></p>
              </div>
              <div class="field" data-field>
                <label for="phone">Mobile number</label>
                <input id="phone" name="phone" type="tel" required inputmode="tel" autocomplete="tel" value="{{ old('phone', $customer?->phone) }}"
                       data-validate="phone" aria-describedby="phone-hint" placeholder="071 234 5678"
                       data-required-message="Enter your mobile number" data-invalid-message="Enter a valid Sri Lankan mobile number, for example 071 234 5678">
                <p class="field__hint" id="phone-hint">We use your mobile number to deliver your order and to track it.</p>
                <p class="field__error" id="phone-error" hidden></p>
              </div>
            </div>
          </section>

          <section class="checkout-card" aria-labelledby="co-delivery">
            <h2 class="checkout-card__title" id="co-delivery"><span class="checkout-card__num" aria-hidden="true">2</span> Delivery address</h2>
            <div class="checkout-fields checkout-fields--2">
              <div class="field" data-field>
                <label for="first_name">First name</label>
                <input id="first_name" name="first_name" type="text" required autocomplete="given-name" value="{{ old('first_name') }}" data-required-message="Enter the recipient's first name">
                <p class="field__error" id="first_name-error" hidden></p>
              </div>
              <div class="field" data-field>
                <label for="last_name">Last name</label>
                <input id="last_name" name="last_name" type="text" required autocomplete="family-name" value="{{ old('last_name') }}" data-required-message="Enter the recipient's last name">
                <p class="field__error" id="last_name-error" hidden></p>
              </div>
              <div class="field field--wide" data-field>
                <label for="address">Address</label>
                <input id="address" name="address" type="text" required autocomplete="address-line1" value="{{ old('address') }}" data-required-message="Enter your street address">
                <p class="field__error" id="address-error" hidden></p>
              </div>
              <div class="field" data-field>
                <label for="city">Town or city</label>
                <input id="city" name="city" type="text" required autocomplete="address-level2" value="{{ old('city') }}" data-required-message="Enter your town or city">
                <p class="field__error" id="city-error" hidden></p>
              </div>
              <div class="field" data-field>
                <label for="district">District</label>
                <select id="district" name="district" required autocomplete="address-level1" data-required-message="Choose your district">
                  <option value="">Select district</option>
                  @foreach($districts as $d)
                    <option value="{{ $d }}" @selected(old('district') === $d)>{{ $d }}</option>
                  @endforeach
                </select>
                <p class="field__error" id="district-error" hidden></p>
              </div>
              <div class="field field--wide" data-field>
                <label for="note">Delivery notes (optional)</label>
                <textarea id="note" name="note" rows="3" aria-describedby="note-hint">{{ old('note') }}</textarea>
                <p class="field__hint" id="note-hint">For example: leave with the neighbour, call on arrival.</p>
                <p class="field__error" id="note-error" hidden></p>
              </div>
            </div>
            <div class="eta-card">
              <svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-truck"></use></svg>
              <div>
                <p class="eta-card__title" data-eta-text aria-live="polite">Choose your district to see the delivery estimate</p>
                <p class="eta-card__fee" data-fee-text>Delivery: {{ money(config('kayaa.shipping_fee')) }} (free from {{ money(config('kayaa.free_shipping_over')) }})</p>
              </div>
            </div>
          </section>

          <section class="checkout-card" aria-labelledby="co-payment">
            <h2 class="checkout-card__title" id="co-payment"><span class="checkout-card__num" aria-hidden="true">3</span> Payment</h2>

            @if($onlinePaymentAvailable)
              <fieldset class="pay-choice" data-pay-choice>
                <legend class="pay-choice__legend">Payment method</legend>
                <label class="pay-option">
                  <input class="pay-option__input visually-hidden" type="radio" name="payment_method" value="onepay" @checked(old('payment_method', 'onepay') === 'onepay')>
                  <span class="pay-option__box">
                    <svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-credit-card"></use></svg>
                    <span class="pay-option__body">
                      <span class="pay-option__title">Pay by card · Visa or Mastercard</span>
                      <span class="pay-option__text">You'll be taken to Onepay's secure page to enter your card details. We never see or store your card number.</span>
                      <span class="pay-card__brands"><span class="badge badge--outline">Visa</span><span class="badge badge--outline">Mastercard</span></span>
                    </span>
                  </span>
                </label>
                <label class="pay-option">
                  <input class="pay-option__input visually-hidden" type="radio" name="payment_method" value="cod" @checked(old('payment_method') === 'cod')>
                  <span class="pay-option__box">
                    <svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-banknote"></use></svg>
                    <span class="pay-option__body">
                      <span class="pay-option__title">Cash on delivery</span>
                      <span class="pay-option__text">Pay the courier when your parcel arrives. Please have the exact amount ready, the courier may not carry change.</span>
                    </span>
                  </span>
                </label>
              </fieldset>
            @else
              {{-- No Onepay credentials on this server: cash on delivery is the only path. --}}
              <div data-pay-single>
                <input type="hidden" name="payment_method" value="cod" data-pay-default>
                <div class="pay-card">
                  <svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-banknote"></use></svg>
                  <div>
                    <p class="pay-card__title">Cash on delivery</p>
                    <p class="pay-card__text">Pay the courier when your parcel arrives. Please have the exact amount ready, the courier may not carry change.</p>
                  </div>
                </div>
              </div>
            @endif

            <p class="checkout-terms">By paying you agree to our <a href="{{ route('pages.show', 'terms') }}">Terms</a> and <a href="{{ route('pages.show', 'returns') }}">Returns policy</a>.</p>
            <button type="submit" class="btn btn--primary btn--lg btn--block pay-btn" data-pay-btn>
              <svg class="icon icon--16" aria-hidden="true" focusable="false" data-pay-icon><use href="#i-lock"></use></svg>
              <span data-pay-label data-label-card="Pay now" data-label-cod="Place order">{{ $onlinePaymentAvailable ? 'Pay now' : 'Place order' }}</span>
              <span class="spinner" aria-hidden="true" hidden></span>
            </button>
            <p class="pay-helper" data-pay-helper data-helper-card="Secure payment via Onepay" data-helper-cod="No payment is taken online. You pay the courier on delivery.">
              <svg class="icon icon--16" aria-hidden="true" focusable="false" data-helper-icon><use href="#i-{{ $onlinePaymentAvailable ? 'lock' : 'banknote' }}"></use></svg>
              <span data-helper-text>{{ $onlinePaymentAvailable ? 'Secure payment via Onepay' : 'No payment is taken online. You pay the courier on delivery.' }}</span>
            </p>
          </section>
        </form>
      </div>

      <aside class="checkout-aside" aria-labelledby="co-summary">
        <div class="summary-card">
          <h2 id="co-summary">Order summary</h2>
          <x-store.order-lines :lines="$lines" :cart="$cart" />
        </div>
      </aside>
    </div>

    <script type="application/json" id="district-eta">@json($eta)</script>
  </div>
</x-layouts.store>
