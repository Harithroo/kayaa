<x-layouts.store title="Your cart" nav="cart">
  <div class="section">
  <div class="wrap">
    <h1 class="h2 page" style="margin-bottom:18px">Your cart</h1>

    @if($cart->lines()->isEmpty())
      <div class="empty">
        <h3>Your cart is empty</h3>
        <p>Start with the newborn essentials — they go fastest.</p>
        <a class="btn sm" href="{{ route('shop.index') }}">Shop now</a>
      </div>
    @else
    <div class="split">
      <div class="main">
        @foreach($cart->lines() as $line)
          @php $v = $line->variant; $tints = ['butter','sage','cloud','blush','sand']; @endphp
          <div class="line">
            <a href="{{ route('products.show', $v->product) }}"><x-store.photo :image="$v->product->primaryImage()" :tint="$tints[$v->product_id % 5]" cap="" /></a>
            <div>
              <div class="linetop">
                <div>
                  <p style="margin:0 0 2px;font-weight:500"><a href="{{ route('products.show', $v->product) }}" style="color:inherit;text-decoration:none">{{ $v->product->name }}</a></p>
                  <p class="muted" style="margin:0 0 10px;font-size:13px">{{ $v->label() }}</p>
                </div>
                <p class="price" style="margin:0">{{ money($line->lineTotal) }}</p>
              </div>
              <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
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
        <p style="margin:20px 0 0"><a href="{{ route('shop.index') }}" class="small" style="text-decoration:none">← Continue shopping</a></p>
      </div>

      <div class="side">
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
      </div>
    </div>
    @endif
  </div>
  </div>
</x-layouts.store>
