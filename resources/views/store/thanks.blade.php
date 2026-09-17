<x-layouts.store title="Order placed" nav="orders">
  <div class="wrap">
    <div class="confirm">
      <div class="ring"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m4 12.5 5 5L20 6.5"/></svg></div>
      <h1>Order placed</h1>
      <p class="lead">Order <strong>{{ $order->reference }}</strong> is confirmed, {{ $order->first_name }}. Keep this number to track it.
        @if($order->payment_method === 'cod')Have {{ money($order->total) }} in cash ready for the courier.@endif</p>
      <div class="panel">
        <div class="row"><span>Paid by</span><span>{{ $order->payment_method === 'cod' ? 'Cash on delivery' : 'PayHere' }}</span></div>
        <div class="row"><span>Delivering to</span><span>{{ $order->city }}, {{ $order->district }}</span></div>
        <div class="row"><span>Arrives</span><span>{{ in_array($order->district, ['Colombo', 'Gampaha']) ? '1–2 days' : '2–4 days' }}</span></div>
        <div class="row"><span>Items</span><span>{{ $order->items->sum('qty') }}</span></div>
        <div class="row total"><span>Total</span><span>{{ money($order->total) }}</span></div>
      </div>
      <div class="btnrow">
        <a class="btn dark" href="{{ route('orders.track') }}">Track my order</a>
        <a class="btn ghost" href="{{ route('shop.index') }}">Keep shopping</a>
      </div>
    </div>
  </div>
</x-layouts.store>
