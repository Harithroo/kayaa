<x-layouts.store title="Track your order" nav="orders">
  <div class="section">
  <div class="wrap" style="max-width:520px">
    <h1 class="h2 page">Track your order</h1>
    <p class="muted small" style="margin:0 0 20px">Enter the order number from your confirmation and the mobile number you used.</p>
    <form method="post" action="{{ route('orders.track') }}">
      @csrf
      <x-store.field name="reference" label="Order number" placeholder="KY-260917-AB12" required />
      <x-store.field name="phone" label="Mobile number" type="tel" inputmode="tel" required />
      <button class="btn dark" style="width:100%">Find my order</button>
    </form>

    @if($order)
      @php
        $steps = ['pending' => 'Received', 'confirmed' => 'Confirmed', 'shipped' => 'On its way', 'delivered' => 'Delivered'];
        $idx = array_search($order->status, array_keys($steps), true);
      @endphp
      <div class="summary" style="margin-top:28px">
        <p class="eyebrow">{{ $order->reference }}</p>
        @if($order->status === 'cancelled')
          <p><strong>This order was cancelled.</strong> If that's a surprise, message us on WhatsApp.</p>
        @else
          <ul class="steps">
            @foreach($steps as $key => $label)
              @php $done = $idx !== false && array_search($key, array_keys($steps), true) <= $idx; @endphp
              <li @class(['done' => $done])><i></i>{{ $label }}
                @if($key === 'shipped' && $order->shipped_at)<span class="muted" style="font-size:13px">· {{ $order->shipped_at->format('d M') }}</span>@endif
              </li>
            @endforeach
          </ul>
        @endif
        <p class="muted" style="font-size:13.5px;margin:0">Placed {{ $order->created_at->format('d M Y') }} · {{ $order->items->sum('qty') }} items · {{ money($order->total) }} · {{ $order->payment_method === 'cod' ? 'Cash on delivery' : 'Paid online' }}</p>
      </div>
    @endif
  </div>
  </div>
</x-layouts.store>
