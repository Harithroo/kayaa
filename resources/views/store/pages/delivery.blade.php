{{-- Delivery, with the full copy from the prototype's html/delivery.html
     (docs/content/delivery.md). The per-district tables and every fee are
     written from config/kayaa.php, never typed into the page. The prototype's
     "to confirm" editorial markers are left out: they are notes for the client,
     not copy for a shopper. --}}
@php
  $days = config('kayaa.delivery_days');
  $eta = function (string $district) use ($days) {
    [$min, $max] = $days[$district] ?? $days['default'];
    $window = $min === $max ? $max : $min.'–'.$max;
    return $window.' working '.Str::plural('day', $max);
  };
  // Districts by province, so the tables read the way the prototype's do.
  $provinces = [
    'Western' => ['Colombo', 'Gampaha', 'Kalutara'],
    'Central' => ['Kandy', 'Matale', 'Nuwara Eliya'],
    'Southern' => ['Galle', 'Matara', 'Hambantota'],
    'Northern' => ['Jaffna', 'Kilinochchi', 'Mannar', 'Mullaitivu', 'Vavuniya'],
    'Eastern' => ['Batticaloa', 'Ampara', 'Trincomalee'],
    'North Western' => ['Kurunegala', 'Puttalam'],
    'North Central' => ['Anuradhapura', 'Polonnaruwa'],
    'Uva' => ['Badulla', 'Monaragala'],
    'Sabaragamuwa' => ['Ratnapura', 'Kegalle'],
  ];
  $colombo = $eta('Colombo');
  $island = $eta('default');
@endphp
<x-store.content-page title="Delivery" heading="Delivery across Sri Lanka" page="delivery"
  metaDescription="How Kayaa delivers baby clothing island-wide: estimates by district, the delivery fee, how tracking works and what to do if a parcel is late."
  lede="We deliver baby clothing island-wide by courier."
  :toc="[
    '#estimates' => 'Where and how fast',
    '#fee' => 'What it costs',
    '#payment' => 'Payment',
    '#how' => 'How delivery works',
    '#tracking' => 'Tracking your order',
    '#tips' => 'Tips for a smooth delivery',
    '#not-home' => 'Missed or delayed deliveries',
    '#damaged' => 'Damaged or faulty parcels',
    '#questions' => 'Questions?',
  ]">

  <h2 id="estimates">Where and how fast <x-store.anchor id="estimates" label="Where and how fast" /></h2>
  <p>Colombo and suburbs usually arrive in {{ $colombo }}. The rest of the island takes {{ $island }}. Orders placed before 2pm on a working day are dispatched the same day.</p>
  <p>When you choose your district at checkout we show the estimate for your address. The tables below list every district.</p>

  @foreach($provinces as $province => $districts)
    @php $slug = Str::slug($province); @endphp
    <h3 id="{{ $slug }}">{{ $province }} Province <x-store.anchor :id="$slug" :label="$province.' Province'" /></h3>
    <div class="table-wrap">
      <table class="size-table size-table--two">
        <caption class="visually-hidden">Delivery estimates, {{ $province }} Province</caption>
        <thead><tr><th scope="col">District</th><th scope="col">Estimated delivery</th></tr></thead>
        <tbody>
          @foreach($districts as $district)
            <tr><th scope="row">{{ $district }}</th><td data-label="Estimated delivery">{{ $eta($district) }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endforeach

  <p>Working days are Monday to Friday, excluding public holidays and Poya days. Estimates are not guarantees: weather and courier delays can affect them.</p>

  <h2 id="fee">What it costs <x-store.anchor id="fee" label="What it costs" /></h2>
  <p>Delivery is {{ money(config('kayaa.shipping_fee')) }} per order, and free when your order is {{ money(config('kayaa.free_shipping_over')) }} or more. The fee is added at checkout and shown before you pay.</p>

  <h2 id="payment">Payment <x-store.anchor id="payment" label="Payment" /></h2>
  <div class="notice">
    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg>
    <div><p>You choose how to pay at checkout. Card payments (Visa and Mastercard) are taken securely through our payment partner, and we never see your full card number.</p></div>
  </div>

  <h2 id="how">How delivery works <x-store.anchor id="how" label="How delivery works" /></h2>
  <ol class="how-steps how-steps--row" role="list">
    <li><span class="how-steps__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-shopping-bag"></use></svg></span><h3>Order placed</h3><p>You pay online and receive your order number.</p></li>
    <li><span class="how-steps__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-package"></use></svg></span><h3>Packed</h3><p>We confirm and pack your order.</p></li>
    <li><span class="how-steps__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-truck"></use></svg></span><h3>On its way</h3><p>Your parcel goes with our courier. We will also WhatsApp you when it leaves us.</p></li>
    <li><span class="how-steps__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg></span><h3>Delivered</h3><p>Please check the parcel when it arrives.</p></li>
  </ol>

  <h2 id="tracking">Tracking your order <x-store.anchor id="tracking" label="Tracking your order" /></h2>
  <p>Open <a href="{{ route('orders.track') }}">Track order</a> and enter your order number and the mobile number you used at checkout. You will see one of these statuses:</p>
  <ul>
    <li><strong>Pending:</strong> we have your order and are checking it.</li>
    <li><strong>Confirmed:</strong> your order is accepted and being prepared.</li>
    <li><strong>Shipped:</strong> your parcel is on its way.</li>
    <li><strong>Delivered:</strong> your parcel has arrived.</li>
    <li><strong>Cancelled:</strong> the order was cancelled.</li>
  </ul>
  <p>Payment has its own status: pending, paid, failed or refunded.</p>

  <h2 id="tips">Tips for a smooth delivery <x-store.anchor id="tips" label="Tips for a smooth delivery" /></h2>
  <ul>
    <li>Add a landmark or a short direction in the delivery notes.</li>
    <li>Give a mobile number that someone can answer.</li>
    <li>Choose your district carefully: it sets your delivery estimate.</li>
  </ul>

  <h2 id="not-home">Missed or delayed deliveries <x-store.anchor id="not-home" label="Missed or delayed deliveries" /></h2>
  <p>If your parcel is later than the estimate, contact us with your order number and we'll chase it with the courier.</p>

  <h2 id="damaged">Damaged or faulty parcels <x-store.anchor id="damaged" label="Damaged or faulty parcels" /></h2>
  <p>If your parcel arrives damaged, send us a photo within 7 days with your order number. See our <a href="{{ route('pages.show', 'returns') }}">returns policy</a>.</p>

  <h2 id="questions">Questions? <x-store.anchor id="questions" label="Questions?" /></h2>
  <p>WhatsApp us (Mon–Sat 9am–6pm) or use the <a href="{{ route('pages.contact') }}">contact form</a>.</p>

  <x-slot:after>
    <x-store.help-card />
  </x-slot:after>
</x-store.content-page>
