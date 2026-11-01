@props(['cart', 'cartCount' => null])
{{-- The inner panel of the cart drawer, which is also exactly what GET /cart/panel
     returns after every change. Prototype source: tools/partials/cart-drawer.html.
     Quantity and remove are real forms — the server owns the cart. --}}
@php
  $lines = $cart->lines();
  $count = $cartCount ?? $cart->count();
  $freeOver = (int) config('kayaa.free_shipping_over');
  $remaining = $cart->remainingForFreeShipping();
  $subtotal = $cart->subtotal();
@endphp
<div class="cart-panel" data-cart-panel data-state="{{ $lines->isEmpty() ? 'empty' : 'items' }}" data-free-threshold="{{ (int) ($freeOver / 100) }}">
  <div class="drawer__head">
    <h2 class="drawer__title" id="cart-title">Your bag (<span data-cart-count-label>{{ $count }}</span>)</h2>
    <button type="button" class="icon-btn icon-btn--plain" data-close-layer aria-label="Close bag">
      <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-x"></use></svg>
    </button>
  </div>

  @if($lines->isNotEmpty())
    <div class="cart-panel__filled">
      <div class="drawer__body">
        @if($freeOver > 0)
          <div class="cart-progress">
            <p class="cart-progress__text" data-cart-progress-text aria-live="polite">
              @if($remaining > 0){{ money($remaining) }} away from free delivery @else Free island-wide delivery unlocked @endif
            </p>
            <progress class="progress" value="{{ min($subtotal, $freeOver) }}" max="{{ $freeOver }}" data-cart-progress>
              {{ (int) (min($subtotal, $freeOver) / max($freeOver, 1) * 100) }}%
            </progress>
          </div>
        @endif
        <ul class="cart-items" role="list" data-cart-items>
          @foreach($lines as $line)
            @php $v = $line->variant; $p = $v->product; @endphp
            <li class="cart-item" data-cart-item data-unit-price="{{ (int) ($v->price / 100) }}">
              <x-store.photo class="media--tile cart-item__media" :image="$p->primaryImage()" :alt="$p->name" :seed="$p->id" ratio="1 / 1" data-item-media />
              <div class="cart-item__body">
                <p class="cart-item__name"><a href="{{ route('products.show', $p) }}" data-item-name>{{ $p->name }}</a></p>
                <p class="cart-item__meta" data-item-meta>{{ $v->label() }}</p>
                <div class="cart-item__row">
                  <form class="cart-item__form" method="post" action="{{ route('cart.update', $v) }}" data-qty-form>
                    @csrf @method('PATCH')
                    <div class="qty" data-qty>
                      <button type="button" class="qty__btn" data-qty-minus aria-label="Decrease quantity" @disabled($line->qty <= 1)>
                        <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-minus"></use></svg>
                      </button>
                      <input class="qty__input" type="text" name="qty" inputmode="numeric" pattern="[0-9]*" value="{{ $line->qty }}"
                             data-qty-input data-autosubmit aria-label="Quantity for {{ $p->name }}">
                      <button type="button" class="qty__btn" data-qty-plus aria-label="Increase quantity" @disabled($line->qty >= min(10, $v->stock))>
                        <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-plus"></use></svg>
                      </button>
                    </div>
                    <noscript><button type="submit" class="btn btn--secondary btn--sm">Update</button></noscript>
                  </form>
                  <p class="price" data-line-total>{{ money($line->lineTotal) }}</p>
                </div>
              </div>
              <form class="cart-item__form" method="post" action="{{ route('cart.remove', $v) }}">
                @csrf @method('DELETE')
                <button type="submit" class="icon-btn icon-btn--plain cart-item__remove" aria-label="Remove {{ $p->name }}">
                  <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-trash-2"></use></svg>
                </button>
              </form>
            </li>
          @endforeach
        </ul>
      </div>
      <div class="drawer__foot">
        <p class="cart-summary"><span>Subtotal</span> <span class="cart-summary__value" data-cart-subtotal>{{ money($subtotal) }}</span></p>
        <p class="cart-note">Delivery calculated at checkout · Pay securely by card</p>
        <div class="cart-actions">
          <a class="btn btn--primary btn--lg btn--block" href="{{ route('checkout.show') }}">Checkout</a>
          <a class="btn btn--secondary btn--block" href="{{ route('cart.index') }}">View bag</a>
        </div>
      </div>
    </div>
  @else
    <div class="cart-panel__empty">
      <div class="empty-state">
        <span class="empty-state__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-shopping-bag"></use></svg></span>
        <h3 class="empty-state__title">Your bag is empty</h3>
        <a class="btn btn--primary" href="{{ route('shop.index') }}" data-autofocus-empty>Start shopping</a>
      </div>
    </div>
  @endif
</div>
