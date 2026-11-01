{{-- The full bag, from the prototype's html/cart.html. Server-rendered: the
     prototype built its lines from a <template>, which is only needed when the
     cart lives in the browser. cart.js still runs the sticky checkout bar. --}}
@php
  $lines = $cart->lines();
  $count = $cart->count();
  $subtotal = $cart->subtotal();
  $shipping = $cart->shipping();
  $freeOver = (int) config('kayaa.free_shipping_over');
  $remaining = $cart->remainingForFreeShipping();
  $low = \App\Models\ProductVariant::lowStockThreshold();
  // A line whose variant has gone means checkout cannot proceed until it is removed.
  $blocked = $lines->contains(fn ($line) => ! $line->variant->inStock());
@endphp
<x-layouts.store title="Your bag" page="cart">
  <div class="container cart-page" data-cart-page data-low-stock-threshold="{{ $low }}">
    <h1 class="cart-page__title" data-cart-title>Your bag ({{ $count }} {{ Str::plural('item', $count) }})</h1>

    @if(session('cart_stock_notice'))
      <div class="alert alert--error cart-notice" role="alert">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
        <div><p>{{ session('cart_stock_notice') }}</p></div>
      </div>
    @endif

    @if($lines->isNotEmpty())
      <div class="cart-layout" data-cart-filled>
        <section class="cart-layout__lines" aria-label="Items in your bag">
          <ul class="cart-lines" role="list" data-cart-lines>
            @foreach($lines as $line)
              @php
                $v = $line->variant;
                $p = $v->product;
                $unavailable = ! $v->inStock();
                $lowStock = ! $unavailable && $v->stock <= $low;
              @endphp
              <li @class(['cart-line', 'is-unavailable' => $unavailable]) data-cart-line>
                <x-store.photo class="media--tile cart-line__media" :image="$p->primaryImage()" :alt="$p->name" :seed="$p->id" ratio="1 / 1" data-line-media />
                <div class="cart-line__body">
                  <p class="cart-line__name"><a href="{{ route('products.show', $p) }}" data-line-name>{{ $p->name }}</a></p>
                  <p class="cart-line__meta" data-line-meta>{{ $v->label() }}</p>
                  <p @class(['price', 'price--sale' => $v->isOnSale()]) data-line-unit>
                    <span class="price__now">{{ money($v->price) }}</span>
                    @if($v->isOnSale())<s class="price__was">{{ money($v->compare_at_price) }}</s>@endif
                  </p>
                  @if($unavailable)
                    <p class="cart-line__note" data-line-note><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg> <span data-line-note-text>No longer available</span></p>
                  @elseif($lowStock)
                    <p class="cart-line__note" data-line-note><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg> <span data-line-note-text>Only {{ $v->stock }} left</span></p>
                  @endif
                </div>
                <div class="cart-line__actions">
                  <form class="cart-line__qty" method="post" action="{{ route('cart.update', $v) }}" data-cart-update-form>
                    @csrf @method('PATCH')
                    <div class="qty" data-qty data-qty-max="{{ max(1, min(10, $v->stock)) }}">
                      <button type="button" class="qty__btn" data-qty-minus aria-label="Decrease quantity" @disabled($line->qty <= 1 || $unavailable)><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-minus"></use></svg></button>
                      <input class="qty__input" type="text" inputmode="numeric" pattern="[0-9]*" name="qty" value="{{ $line->qty }}" data-qty-input data-autosubmit aria-label="Quantity for {{ $p->name }}" @disabled($unavailable)>
                      <button type="button" class="qty__btn" data-qty-plus aria-label="Increase quantity" @disabled($line->qty >= min(10, $v->stock))><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-plus"></use></svg></button>
                    </div>
                    <noscript><button type="submit" class="btn btn--secondary btn--sm">Update</button></noscript>
                  </form>
                  <p class="cart-line__total"><span class="visually-hidden">Line total </span><span data-line-total>{{ money($line->lineTotal) }}</span></p>
                  <form class="cart-line__remove-form" method="post" action="{{ route('cart.remove', $v) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-text" data-cart-remove>
                      <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-trash-2"></use></svg> Remove<span class="visually-hidden"> {{ $p->name }}</span>
                    </button>
                  </form>
                </div>
              </li>
            @endforeach
          </ul>
        </section>

        <aside class="cart-layout__summary" aria-labelledby="summary-title">
          <div class="summary-card">
            <h2 id="summary-title">Order summary</h2>
            <dl class="totals">
              <div class="totals__row"><dt>Subtotal</dt><dd data-cart-subtotal>{{ money($subtotal) }}</dd></div>
              <div class="totals__row"><dt>Delivery</dt><dd data-cart-delivery>{{ $shipping === 0 ? 'Free' : money($shipping) }}</dd></div>
              <div class="totals__row totals__row--total"><dt>Total</dt><dd data-cart-total>{{ money($cart->total()) }}</dd></div>
            </dl>
            @if($freeOver > 0)
              <div class="cart-progress">
                <p class="cart-progress__text" data-cart-progress-text aria-live="polite">
                  @if($remaining > 0){{ money($remaining) }} away from free delivery @else You've unlocked free delivery @endif
                </p>
                <progress class="progress" value="{{ min($subtotal, $freeOver) }}" max="{{ $freeOver }}" data-cart-progress>
                  {{ (int) (min($subtotal, $freeOver) / max($freeOver, 1) * 100) }}%
                </progress>
              </div>
            @endif
            @if($blocked)
              <p class="summary-card__hint" id="checkout-hint" data-checkout-hint>Remove the unavailable item to continue to checkout.</p>
              {{-- The design system styles .btn[aria-disabled="true"]. --}}
              <span class="btn btn--primary btn--lg btn--block" role="link" aria-disabled="true" aria-describedby="checkout-hint" data-checkout-btn>Checkout</span>
            @else
              <a class="btn btn--primary btn--lg btn--block" href="{{ route('checkout.show') }}" data-checkout-btn data-checkout-link>Checkout</a>
            @endif
            <a class="link-arrow" href="{{ route('shop.index') }}">Continue shopping <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
            <ul class="assure" role="list">
              <li><span class="assure__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-credit-card"></use></svg></span><span>Secure card payment · Visa &amp; Mastercard</span></li>
              <li><a class="assure__link" href="{{ route('pages.show', 'returns') }}"><span class="assure__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-rotate-ccw"></use></svg></span><span>Easy returns · see our returns page</span></a></li>
            </ul>
          </div>
        </aside>
      </div>
    @else
      <div class="cart-empty" data-cart-empty>
        <div class="empty-state">
          <span class="empty-state__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-shopping-bag"></use></svg></span>
          <h2 class="empty-state__title">Your bag is empty</h2>
          <a class="btn btn--primary" href="{{ route('shop.index') }}">Start shopping</a>
          @if($navSizes->isNotEmpty())
            <p class="cart-empty__ages">Or shop by age</p>
            <ul class="chip-list" role="list">
              @foreach($navSizes as $size)
                <li><a class="chip" href="{{ route('shop.index', ['size' => $size->label]) }}">{{ $size->label }}</a></li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
    @endif
  </div>

  @if($lines->isNotEmpty())
    <div class="sticky-atc" data-sticky-atc inert>
      <div class="sticky-atc__info">
        <p class="sticky-atc__price" data-cart-total>{{ money($cart->total()) }}</p>
        <p class="sticky-atc__size">Total · <span data-cart-count-text>{{ $count }} {{ Str::plural('item', $count) }}</span></p>
      </div>
      <a class="btn btn--primary" href="{{ route('checkout.show') }}" data-checkout-link>Checkout</a>
    </div>
  @endif
</x-layouts.store>
