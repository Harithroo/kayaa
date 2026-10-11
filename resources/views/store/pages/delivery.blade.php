{{-- Delivery, in the design system's content shell. The copy is the store's own;
     the fees come from the store config, never typed into the page. --}}
<x-store.content-page title="Delivery" heading="Delivery across Sri Lanka" page="delivery"
  lede="We deliver island-wide by courier."
  :toc="['#estimates' => 'Where and how fast', '#fee' => 'What it costs', '#cod' => 'Cash on delivery', '#tracking' => 'Tracking']">
  <h2 id="estimates">Where and how fast <a class="anchor" href="#estimates" aria-label="Link to this section: Where and how fast"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-link"></use></svg></a></h2>
  <p>We deliver island-wide by courier. Colombo and suburbs usually arrive in 1–2 working days; the rest of the island in 2–4. Orders placed before 2pm on a working day are dispatched the same day.</p>

  <h2 id="fee">What it costs <a class="anchor" href="#fee" aria-label="Link to this section: What it costs"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-link"></use></svg></a></h2>
  <p>Delivery is {{ money(config('kayaa.shipping_fee')) }} per order, and free when your order is {{ money(config('kayaa.free_shipping_over')) }} or more.</p>

  <h2 id="cod">Cash on delivery <a class="anchor" href="#cod" aria-label="Link to this section: Cash on delivery"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-link"></use></svg></a></h2>
  <p>Choose cash on delivery at checkout and pay the courier when your parcel arrives. Please have the exact amount ready — the courier may not carry change.</p>

  <h2 id="tracking">Tracking <a class="anchor" href="#tracking" aria-label="Link to this section: Tracking"><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-link"></use></svg></a></h2>
  <p>Use your order number and mobile number on the <a href="{{ route('orders.track') }}">track your order</a> page. We'll also WhatsApp you when the parcel leaves us.</p>
</x-store.content-page>
