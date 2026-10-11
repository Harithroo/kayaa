{{-- One order, from the prototype's html/account/order.html. --}}
@php
  $steps = [['Order placed', 'i-check'], ['Confirmed', 'i-check'], ['Shipped', 'i-truck'], ['Delivered', 'i-home']];
  $statusIndex = ['pending' => 0, 'confirmed' => 1, 'shipped' => 2, 'delivered' => 3];
  $current = $statusIndex[$order->status] ?? 0;
  $cancelled = $order->status === 'cancelled';
  $days = config('kayaa.delivery_days.'.$order->district, config('kayaa.delivery_days.default'));
@endphp
<x-layouts.store :title="'Order '.$order->reference" page="account">
  <div class="container account">
    <div class="account__layout">
      <x-store.account-nav active="orders" />
      <div class="account__content">
        <div class="order-detail" data-order-found>
          <a class="back-link" href="{{ route('account.index') }}"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-left"></use></svg> My orders</a>
          <header class="order-head">
            <div>
              <h1 class="order-head__ref">Order <span data-o-ref>{{ $order->reference }}</span></h1>
              <p class="order-head__meta" data-o-date>Placed {{ $order->created_at->format('j M Y') }}</p>
            </div>
            <div class="order-head__badges">
              <span class="status status--{{ $order->status }}">{{ Str::ucfirst($order->status) }}</span>
              <span class="status status--{{ $order->payment_status }}">{{ Str::ucfirst($order->payment_status) }}</span>
            </div>
          </header>

          <section class="summary-card" aria-labelledby="od-status">
            <h2 id="od-status">Order status</h2>
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
              <form method="post" action="{{ $order->payUrl() }}">@csrf<button type="submit" class="btn btn--primary track-retry">Resume payment</button></form>
            @endif
            @if($order->canCustomerCancel())
              <form method="post" action="{{ $order->cancelUrl() }}">@csrf<button type="submit" class="btn btn--ghost">Cancel order</button></form>
            @endif
          </section>

          <div class="order-detail__grid">
            <section class="summary-card" aria-labelledby="od-items">
              <h2 id="od-items">Items</h2>
              <ul class="order-lines" role="list" data-o-lines>
                @foreach($order->items as $item)
                  <li class="order-line">
                    <x-store.photo class="media--tile order-line__media" :image="null" alt="" :seed="$item->id" ratio="1 / 1" />
                    <div>
                      <p class="order-line__name">{{ $item->product_name }}</p>
                      <p class="order-line__meta">{{ collect([$item->variant_label, 'Qty '.$item->qty])->filter()->join(' · ') }}</p>
                    </div>
                    <p class="order-line__price">{{ money($item->line_total) }}</p>
                  </li>
                @endforeach
              </ul>
            </section>
            <div class="order-detail">
              <section class="summary-card" aria-labelledby="od-totals">
                <h2 id="od-totals">Payment</h2>
                <dl class="totals">
                  <div class="totals__row"><dt>Subtotal</dt><dd data-o-subtotal>{{ money($order->subtotal) }}</dd></div>
                  <div class="totals__row"><dt>Delivery</dt><dd data-o-delivery>{{ $order->shipping === 0 ? 'Free' : money($order->shipping) }}</dd></div>
                  <div class="totals__row totals__row--total"><dt>Total</dt><dd data-o-total>{{ money($order->total) }}</dd></div>
                  <div class="totals__row"><dt>Payment method</dt><dd data-o-method>{{ $order->paymentMethodLabel() }}</dd></div>
                  <div class="totals__row"><dt>Payment status</dt><dd><span class="status status--{{ $order->payment_status }}">{{ Str::ucfirst($order->payment_status) }}</span></dd></div>
                </dl>
              </section>
              <section class="summary-card" aria-labelledby="od-delivery">
                <h2 id="od-delivery">Delivery</h2>
                <address class="address-block" data-o-address>
                  {{ $order->customerName() }}<br>{{ $order->address }}<br>{{ $order->city }}, {{ $order->district }}<br>{{ $order->phone }}
                </address>
                @unless($cancelled)
                  <p class="eta-line"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-truck"></use></svg>
                    <span data-o-eta>Estimated delivery to {{ $order->district }}: {{ $days[0] === $days[1] ? $days[1] : $days[0].'–'.$days[1] }} working days</span>
                  </p>
                @endunless
              </section>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-layouts.store>
