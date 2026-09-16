<x-layouts.store title="Your cart">
  <div class="wrap section">
    <div class="sechead"><h1 class="sec">Your cart</h1></div>

    @if($cart->lines()->isEmpty())
      <p class="empty">Your cart is empty. <a href="{{ route('shop.index') }}">Start with something soft</a>.</p>
    @else
    <div class="cart-grid">
      <div>
        @foreach($cart->lines() as $line)
          @php $v = $line->variant; @endphp
          <div class="line">
            <a href="{{ route('products.show', $v->product) }}"><x-store.photo :image="$v->product->primaryImage()" :tint="'t'.(($v->product_id % 6) + 1)" /></a>
            <div>
              <div class="linetop">
                <div>
                  <p style="margin:0 0 2px;font-weight:500"><a href="{{ route('products.show', $v->product) }}" style="color:inherit;text-decoration:none">{{ $v->product->name }}</a></p>
                  <p style="margin:0 0 9px;font-size:13px;color:var(--ink-soft)">{{ $v->label() }}</p>
                </div>
                <p class="price" style="margin:0">{{ money($line->lineTotal) }}</p>
              </div>
              <div style="display:flex;align-items:center;gap:14px">
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
        <p style="margin:20px 0 0"><a href="{{ route('shop.index') }}" style="font-size:14.5px">← Continue shopping</a></p>
      </div>

      <div>
        @if($cart->remainingForFreeShipping() > 0)
          <div class="freeship" style="margin-top:18px">
            <x-store.tick style="color:var(--teal)" />
            <div>Add <strong>{{ money($cart->remainingForFreeShipping()) }}</strong> more for free island-wide delivery.</div>
          </div>
        @else
          <div class="freeship" style="margin-top:18px"><x-store.tick /><div>You've unlocked <strong>free delivery</strong>.</div></div>
        @endif
        <div class="totals">
          <div><span>Subtotal</span><span class="price">{{ money($cart->subtotal()) }}</span></div>
          <div><span>Delivery</span><span class="price">{{ $cart->shipping() === 0 ? 'Free' : money($cart->shipping()) }}</span></div>
          <div class="grand"><span>Total</span><span class="price">{{ money($cart->total()) }}</span></div>
        </div>
        <a class="btn" href="{{ route('checkout.show') }}">Checkout</a>
      </div>
    </div>
    @endif
  </div>
</x-layouts.store>
