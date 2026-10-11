{{-- Order tracking, from the prototype's html/track.html. The prototype looked the
     order up in JS and revealed a hidden result; here the server answers the form
     and renders the result directly. The stepper is the design system's. --}}
@php
  $steps = [
    ['Order placed', 'i-check'],
    ['Confirmed', 'i-check'],
    ['Shipped', 'i-truck'],
    ['Delivered', 'i-home'],
  ];
  $statusIndex = ['pending' => 0, 'confirmed' => 1, 'shipped' => 2, 'delivered' => 3];
  $current = $order ? ($statusIndex[$order->status] ?? 0) : 0;
  $cancelled = $order && $order->status === 'cancelled';
  $waHref = \App\Support\StoreContact::whatsappHref();
@endphp
<x-layouts.store title="Track your order" page="track">
  <div class="container track" data-track-page>
    <h1>Track your order</h1>
    <p class="track__lede">Enter the order number from your confirmation and the mobile number you used.</p>

    @if($errors->any())
      <div class="alert alert--error" role="alert" tabindex="-1" id="track-summary">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
        <div><p class="alert__title">Please check your details</p><ul class="alert__list">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
      </div>
    @endif

    @if(session('error'))
      <div class="track-alerts">
        <div class="alert alert--error" role="alert" tabindex="-1" data-track-alert="notfound">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
          <div><p>{{ session('error') }}</p></div>
        </div>
      </div>
    @endif

    <form class="track-form" method="post" action="{{ route('orders.track') }}" novalidate data-auth-form data-summary="track-summary" data-track-form>
      @csrf
      <div class="track-form__fields">
        <div class="field" data-field>
          <label for="ref">Order number</label>
          <input id="ref" name="reference" type="text" placeholder="KY-261003-A3F9" maxlength="20" autocapitalize="characters"
                 autocomplete="off" spellcheck="false" required value="{{ old('reference') }}" data-required-message="Enter your order number">
          <p class="field__error" id="ref-error" hidden></p>
        </div>
        <div class="field" data-field>
          <label for="phone">Mobile number</label>
          <input id="phone" name="phone" type="tel" required inputmode="tel" autocomplete="tel" placeholder="071 234 5678" value="{{ old('phone') }}"
                 data-validate="phone" aria-describedby="phone-hint" data-required-message="Enter your mobile number"
                 data-invalid-message="Enter a valid Sri Lankan mobile number, for example 071 234 5678">
          <p class="field__hint" id="phone-hint">The number you gave at checkout</p>
          <p class="field__error" id="phone-error" hidden></p>
        </div>
      </div>
      <button type="submit" class="btn btn--primary btn--lg">Find my order</button>
    </form>

    @if($order)
      <div class="track-result" data-track-result>
        <section class="track-card" aria-labelledby="tr-title">
          <h2 class="visually-hidden" id="tr-title">Order status</h2>
          <div class="order-head">
            <div>
              <p class="order-head__ref" data-t-ref>{{ $order->reference }}</p>
              <p class="order-head__meta">
                <span data-t-date>Placed {{ $order->created_at->format('j M Y') }}</span> ·
                <span data-t-updated>Last updated {{ $order->updated_at->format('j M Y') }}</span>
              </p>
            </div>
            <div class="order-head__badges">
              <span class="status status--{{ $order->status }}">{{ Str::ucfirst($order->status) }}</span>
              <span class="status status--{{ $order->payment_status }}">{{ Str::ucfirst($order->payment_status) }}</span>
            </div>
          </div>

          @if($cancelled)
            <div class="alert alert--error" role="status">
              <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
              <div><p>This order was cancelled.</p></div>
            </div>
          @else
            <ol class="stepper" data-stepper>
              @foreach($steps as $i => [$label, $icon])
                <li @class(['stepper__step', 'is-done' => $i < $current, 'is-current' => $i === $current, 'is-upcoming' => $i > $current])
                    @if($i === $current) aria-current="step" @endif>
                  <span class="stepper__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#{{ $i < $current ? 'i-check' : $icon }}"></use></svg></span>
                  <span class="stepper__label">{{ $label }}<span class="visually-hidden"> ({{ $i < $current ? 'completed' : ($i === $current ? 'current step' : 'upcoming') }})</span></span>
                </li>
              @endforeach
            </ol>
          @endif

          @if($order->isPayable())
            <form method="post" action="{{ $order->payUrl() }}">
              @csrf
              <button type="submit" class="btn btn--primary track-retry">Resume payment</button>
            </form>
          @endif

          @php $days = config('kayaa.delivery_days.'.$order->district, config('kayaa.delivery_days.default')); @endphp
          @unless($cancelled)
            <p class="track-eta" data-t-eta>
              <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-truck"></use></svg>
              <span data-t-eta-text>Delivery estimate: {{ $days[0] === $days[1] ? $days[1] : $days[0].'–'.$days[1] }} working days</span>
            </p>
          @endunless
        </section>

        <section class="track-card" aria-labelledby="tr-items">
          <h2 id="tr-items">Items</h2>
          <x-store.order-lines :lines="$order->items" :order="$order" />
          <dl class="totals">
            <div class="totals__row"><dt>Payment method</dt><dd data-t-method>{{ $order->paymentMethodLabel() }}</dd></div>
          </dl>
        </section>

        @if($waHref)
          <section class="track-card track-help" aria-labelledby="tr-help">
            <div>
              <h2 id="tr-help">Questions about your order?</h2>
              <p>Chat to us on WhatsApp and quote <strong>{{ $order->reference }}</strong>.</p>
            </div>
            <a class="btn btn--secondary" href="{{ $waHref }}" target="_blank" rel="noopener">
              <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-message-circle"></use></svg> Chat on WhatsApp
            </a>
          </section>
        @endif
      </div>
    @endif
  </div>
</x-layouts.store>
