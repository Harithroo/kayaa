<x-layouts.store title="Checkout" nav="cart">
  <div class="section">
  <div class="wrap">
    <h1 class="h2 page" style="margin-bottom:18px">Checkout</h1>
    <div class="split">
      <form class="main" method="post" action="{{ route('checkout.store') }}" novalidate>
        @csrf
        <h2 class="h3">Contact</h2>
        <x-store.field name="phone" label="Mobile number" type="tel" inputmode="tel" placeholder="077 412 6690" autocomplete="tel" :value="$customer?->phone" required />
        <x-store.field name="email" label="Email (for the receipt)" type="email" placeholder="you@example.com" autocomplete="email" :value="$customer?->email" />

        <h2 class="h3" style="margin-top:26px">Delivery address</h2>
        <div class="two">
          <x-store.field name="first_name" label="First name" placeholder="Amaya" autocomplete="given-name" :value="$customer ? Str::before($customer->name, ' ') : null" required />
          <x-store.field name="last_name" label="Last name" placeholder="Perera" autocomplete="family-name" :value="$customer && Str::contains($customer->name, ' ') ? Str::after($customer->name, ' ') : null" required />
        </div>
        <x-store.field name="address" label="Address" placeholder="24/3 Pagoda Road" autocomplete="street-address" required />
        <div class="two">
          <x-store.field name="city" label="City" placeholder="Nugegoda" autocomplete="address-level2" required />
          <div @class(['field', 'invalid' => $errors->has('district')])>
            <label for="f-district">District</label>
            <select id="f-district" name="district" required data-district>
              @foreach($districts as $d)<option @selected(old('district', 'Colombo') === $d)>{{ $d }}</option>@endforeach
            </select>
            @error('district')<span class="err">{{ $message }}</span>@enderror
          </div>
        </div>
        <x-store.field name="note" label="Delivery note (optional)" type="textarea" rows="2" placeholder="Landmark, or a better time to deliver" />
        <p class="muted" style="margin:0 0 20px;font-size:13.5px" data-eta>Delivery to Colombo takes 1–2 days.</p>

        <h2 class="h3">Payment</h2>
        <div class="pay">
          <label class="payopt {{ old('payment_method', 'cod') === 'cod' ? 'on' : '' }}">
            <input type="radio" name="payment_method" value="cod" @checked(old('payment_method', 'cod') === 'cod')>
            <span><strong>Cash on delivery</strong><span>Pay the courier in cash when your order arrives.</span></span>
          </label>
          @if(config('services.payhere.merchant_id'))
          <label class="payopt {{ old('payment_method') === 'payhere' ? 'on' : '' }}">
            <input type="radio" name="payment_method" value="payhere" @checked(old('payment_method') === 'payhere')>
            <span><strong>Card or bank — via PayHere</strong><span>Visa, Mastercard, Amex, eZ Cash, FriMi and bank transfer.</span></span>
          </label>
          @endif
        </div>
        <button class="btn dark" style="margin-top:20px;width:100%">Place order · {{ money($cart->total()) }}</button>
        <p class="muted" style="font-size:12.5px;margin-top:12px">By placing this order you agree to our <a href="{{ route('pages.show', 'returns') }}">returns policy</a>. We never store your card details.</p>
      </form>

      <aside class="side summary">
        <h2 class="h3">Order summary</h2>
        @foreach($cart->lines() as $line)
          @php $tints = ['butter','sage','cloud','blush','sand']; @endphp
          <div class="sumline">
            <x-store.photo :image="$line->variant->product->primaryImage()" :tint="$tints[$line->variant->product_id % 5]" cap="" />
            <div class="body">
              <p>{{ $line->variant->product->name }}</p>
              <p class="muted">{{ $line->variant->label() }} · Qty {{ $line->qty }}</p>
            </div>
            <p class="price" style="margin:0;font-size:14px">{{ money($line->lineTotal) }}</p>
          </div>
        @endforeach
        <div class="totals" style="padding:8px 0 0">
          <div><span>Subtotal</span><span style="font-variant-numeric:tabular-nums">{{ money($cart->subtotal()) }}</span></div>
          <div><span>Delivery</span><span style="font-variant-numeric:tabular-nums">{{ $cart->shipping() === 0 ? 'Free' : money($cart->shipping()) }}</span></div>
          <div class="grand"><span>Total</span><span>{{ money($cart->total()) }}</span></div>
        </div>
      </aside>
    </div>
  </div>
  </div>
</x-layouts.store>
