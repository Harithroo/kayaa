{{-- Order confirmation, from the prototype's html/thank-you.html. The prototype
     carried every state as a hidden block and switched them in JS; the server
     knows the state, so only the right one is rendered. --}}
@php
  $paid = $order->payment_status === 'paid';
  $failed = $order->payment_status === 'failed';
  $cancelled = $order->status === 'cancelled';
  $pendingOnline = ! $paid && ! $failed && ! $cancelled && $order->payment_method === 'onepay';
  $cod = ! $cancelled && $order->payment_method === 'cod';
@endphp
<x-layouts.store title="Order confirmed" page="thank-you" shell="minimal">
  <div class="container thanks" data-thanks-page>

    @if($cancelled)
      <section class="thanks__hero">
        <span class="thanks__icon thanks__icon--error"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg></span>
        <h1 class="thanks__title">This order was cancelled.</h1>
        <p class="thanks__lede">If you were charged, contact us with your order number.</p>
        <div class="thanks__actions">
          <a class="btn btn--primary btn--lg" href="{{ route('shop.index') }}">Continue shopping</a>
          <a class="btn btn--secondary btn--lg" href="{{ route('pages.contact') }}">Contact us</a>
        </div>
      </section>
    @elseif($failed)
      <section class="thanks__hero">
        <span class="thanks__icon thanks__icon--error"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg></span>
        <h1 class="thanks__title">Your payment didn't go through</h1>
        <p class="thanks__lede">If you were charged, contact us with your order number.</p>
        <div class="thanks__actions">
          @if($canPay)
            <form method="post" action="{{ $order->payUrl() }}">@csrf<button type="submit" class="btn btn--primary btn--lg">Resume payment</button></form>
          @endif
          <a class="btn btn--secondary btn--lg" href="{{ route('shop.index') }}">Continue shopping</a>
        </div>
      </section>
    @elseif($pendingOnline)
      <section class="thanks__hero">
        <span class="thanks__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-clock"></use></svg></span>
        <h1 class="thanks__title">We're confirming your payment</h1>
        <p class="thanks__lede">This can take a few minutes. Please don't pay again.</p>
        <div class="thanks__actions">
          <a class="btn btn--primary btn--lg" href="{{ $order->thanksUrl() }}">Refresh status</a>
          <a class="btn btn--secondary btn--lg" href="{{ route('pages.contact') }}">Contact us</a>
        </div>
      </section>
    @else
      <section class="thanks__hero">
        <span class="thanks__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg></span>
        <h1 class="thanks__title">Order confirmed</h1>
        <p class="thanks__thanks">Thank you, <span data-customer-name>{{ $order->first_name }}</span></p>
        @if($cod)
          <p class="thanks__lede">You'll pay <strong data-cod-total>{{ money($order->total) }}</strong> to the courier on delivery.</p>
        @else
          <p class="thanks__lede">We'll pack your order and send it to you. Use your order number to track it.</p>
        @endif
        @if($order->email)
          <p class="thanks__email"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-mail"></use></svg> <span>A confirmation has been sent to <strong>{{ $order->email }}</strong></span></p>
        @endif
        <div class="thanks__actions">
          <a class="btn btn--primary btn--lg" href="{{ $order->trackUrl() }}" data-track-link>Track your order</a>
          <a class="btn btn--secondary btn--lg" href="{{ route('shop.index') }}">Continue shopping</a>
        </div>
      </section>
    @endif

    <div class="thanks__grid" data-thanks-details>
      <section class="summary-card thanks__wide" aria-labelledby="ty-ref">
        <h2 class="visually-hidden" id="ty-ref">Order details</h2>
        <div class="order-ref">
          <p class="order-ref__label">Order number</p>
          <p class="order-ref__value" data-order-ref>{{ $order->reference }}</p>
          <button type="button" class="btn btn--ghost btn--sm" data-copy-ref aria-label="Copy order number">
            <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-copy"></use></svg> <span data-copy-label>Copy</span>
          </button>
          <span class="visually-hidden" role="status" aria-live="polite" data-copy-status></span>
        </div>
        <div class="pay-line">
          <span class="pay-line__method">
            <svg class="icon icon--16" aria-hidden="true" focusable="false" data-method-icon><use href="#i-{{ $cod ? 'banknote' : 'credit-card' }}"></use></svg>
            <span data-method-label>{{ $order->paymentMethodLabel() }}</span>
          </span>
          <span class="status status--{{ $order->payment_status }}" data-payment-status>{{ Str::ucfirst($order->payment_status) }}</span>
        </div>
      </section>

      <section class="summary-card" aria-labelledby="ty-items">
        <h2 id="ty-items">Items</h2>
        <x-store.order-lines :lines="$order->items" :order="$order" />
      </section>

      <section class="summary-card" aria-labelledby="ty-delivery">
        <h2 id="ty-delivery">Delivery</h2>
        <address class="address-block" data-order-address>
          {{ $order->customerName() }}<br>
          {{ $order->address }}<br>
          {{ $order->city }}, {{ $order->district }}<br>
          {{ $order->phone }}
        </address>
        @php $days = config('kayaa.delivery_days.'.$order->district, config('kayaa.delivery_days.default')); @endphp
        <p class="eta-line"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-truck"></use></svg>
          <span data-order-eta>Estimated delivery to {{ $order->district }}: {{ $days[0] === $days[1] ? $days[1] : $days[0].'–'.$days[1] }} working days</span>
        </p>
      </section>

      @guest
        <aside class="summary-card thanks-account thanks__wide" aria-label="Create an account">
          <p>Create an account with the same email to see this order later.</p>
          <a class="btn btn--secondary" href="{{ route('account.register') }}">Create an account</a>
        </aside>
      @endguest

      @if($canCancel)
        <aside class="summary-card thanks__wide" aria-label="Cancel this order">
          <p>Changed your mind? You can still cancel this order.</p>
          <form method="post" action="{{ $order->cancelUrl() }}">
            @csrf
            <button type="submit" class="btn btn--ghost">Cancel order</button>
          </form>
        </aside>
      @endif
    </div>
  </div>
</x-layouts.store>
