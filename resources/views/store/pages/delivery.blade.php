<x-layouts.store title="Delivery">
  <div class="section">
  <div class="wrap prose">
    <h1>Delivery</h1>
    <h2>Where and how fast</h2>
    <p>We deliver island-wide by courier. Colombo and suburbs usually arrive in 1–2 working days; the rest of the island in 2–4. Orders placed before 2pm on a working day are dispatched the same day.</p>
    <h2>What it costs</h2>
    <p>Delivery is {{ money(config('kayaa.shipping_fee')) }} per order, and free when your order is {{ money(config('kayaa.free_shipping_over')) }} or more.</p>
    <h2>Cash on delivery</h2>
    <p>Choose cash on delivery at checkout and pay the courier when your parcel arrives. Please have the exact amount ready — the courier may not carry change.</p>
    <h2>Tracking</h2>
    <p>Use your order number and mobile number on the <a href="{{ route('orders.track') }}">track your order</a> page. We'll also WhatsApp you when the parcel leaves us.</p>
  </div>
  </div>
</x-layouts.store>
