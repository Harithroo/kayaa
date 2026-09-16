<x-layouts.store title="Order placed">
  <div class="wrap section">
    <div class="thanks">
      <x-store.tick class="big-tick" />
      <p class="eyebrow">Order placed</p>
      <h1 class="sec" style="font-size:27px">Thank you, {{ $order->first_name }}.</h1>
      <p class="ref">{{ $order->reference }}</p>
      <p class="muted">Save this number — you'll need it to <a href="{{ route('orders.track') }}">track your order</a>.
        @if($order->payment_method === 'cod')Have <strong>{{ money($order->total) }}</strong> in cash ready for the courier.@endif
        We deliver to {{ $order->district }} in {{ $order->district === 'Colombo' ? '1–2' : '2–4' }} days.</p>
      @if($order->email)<p class="muted" style="font-size:13.5px">A receipt has been sent to {{ $order->email }}.</p>@endif
      <a class="btn ghost" style="margin-top:18px" href="{{ route('shop.index') }}">Keep shopping</a>
    </div>

    <div class="summary" style="max-width:560px;margin:10px auto 0">
      <h2 class="h3" style="font-size:15px">What you ordered</h2>
      @foreach($order->items as $item)
        <div style="display:flex;justify-content:space-between;gap:12px;font-size:14px;margin-bottom:8px">
          <span>{{ $item->product_name }} <span class="muted">· {{ $item->variant_label }} × {{ $item->qty }}</span></span>
          <span class="price">{{ money($item->line_total) }}</span>
        </div>
      @endforeach
      <div class="totals" style="padding-bottom:0">
        <div><span>Delivery to {{ $order->city }}, {{ $order->district }}</span><span class="price">{{ $order->shipping === 0 ? 'Free' : money($order->shipping) }}</span></div>
        <div class="grand"><span>Total</span><span class="price">{{ money($order->total) }}</span></div>
      </div>
    </div>
  </div>
</x-layouts.store>
