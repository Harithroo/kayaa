{{-- Cart drawer contents. Re-rendered on its own by /cart/panel after every change. --}}
@php $lines = $cart->lines(); @endphp
<div class="cartpanel-in" data-cart-panel>
  @if($lines->isEmpty())
    <div class="cartempty">
      <h3>Your cart is empty</h3>
      <p class="muted small">Start with the newborn essentials — they go fastest.</p>
      <a class="btn sm" href="{{ route('shop.index') }}">Shop now</a>
    </div>
  @else
    <div class="cartlines">
      @foreach($lines as $line)
        @php $v = $line->variant; $tints = ['butter','sage','cloud','blush','sand']; @endphp
        <div class="cartline">
          <a href="{{ route('products.show', $v->product) }}"><x-store.photo :image="$v->product->primaryImage()" :tint="$tints[$v->product_id % 5]" cap="" /></a>
          <div>
            <div class="linetop">
              <p class="nm"><a href="{{ route('products.show', $v->product) }}">{{ $v->product->name }}</a></p>
              <p class="price">{{ money($line->lineTotal) }}</p>
            </div>
            <p class="muted" style="margin:0 0 8px;font-size:13px">{{ $v->label() }}</p>
            <div class="lineactions">
              <form method="post" action="{{ route('cart.update', $v) }}" class="qty" data-qty-form>
                @csrf @method('PATCH')
                <button type="button" data-step="-1" aria-label="Decrease">−</button>
                <input type="number" name="qty" value="{{ $line->qty }}" min="0" max="10" aria-label="Quantity" onchange="this.form.requestSubmit()">
                <button type="button" data-step="1" aria-label="Increase" @disabled($line->qty >= min(10, $v->stock))>+</button>
              </form>
              <form method="post" action="{{ route('cart.remove', $v) }}">
                @csrf @method('DELETE')
                <button class="remove">Remove</button>
              </form>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="cartfoot">
      <div class="notice">
        <x-store.tick style="margin-top:2px" />
        <div>@if($cart->remainingForFreeShipping() > 0)Add <strong>{{ money($cart->remainingForFreeShipping()) }}</strong> more for free island-wide delivery.@else Free island-wide delivery unlocked.@endif</div>
      </div>
      <div class="totals">
        <div><span>Subtotal</span><span class="price">{{ money($cart->subtotal()) }}</span></div>
        <div><span>Delivery</span><span class="price">{{ $cart->shipping() === 0 ? 'Free' : money($cart->shipping()) }}</span></div>
        <div class="grand"><span>Total</span><span class="price">{{ money($cart->total()) }}</span></div>
      </div>
      <a class="btn" href="{{ route('checkout.show') }}" style="width:100%">Checkout</a>
      <a class="viewcart" href="{{ route('cart.index') }}">View full cart</a>
    </div>
  @endif
</div>
