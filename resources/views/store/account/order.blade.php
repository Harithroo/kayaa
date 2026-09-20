@php
  $steps = ['pending' => 'Received', 'confirmed' => 'Confirmed', 'shipped' => 'On its way', 'delivered' => 'Delivered'];
  $idx = array_search($order->status, array_keys($steps), true);
@endphp
<x-layouts.store :title="$order->reference" nav="account">
  <div class="section">
  <div class="wrap" style="max-width:640px">
    <a class="back" href="{{ route('account.index') }}">← Back to my orders</a>
    <h1 class="h2 page">{{ $order->reference }}</h1>
    <p class="muted small" style="margin:0 0 20px">Placed {{ $order->created_at->format('d M Y') }} · {{ $order->payment_method === 'cod' ? 'Cash on delivery' : 'Paid online' }}</p>

    <div class="summary" style="margin-bottom:20px">
      @if($order->status === 'cancelled')
        <p style="margin:0"><strong>This order was cancelled.</strong> If that's a surprise, message us on WhatsApp.</p>
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
    </div>

    <div class="panel">
      @foreach($order->items as $item)
        <div class="row" style="display:flex;justify-content:space-between;gap:12px;font-size:14.5px;margin-bottom:8px">
          <span>{{ $item->qty }} × {{ $item->product_name }} <span class="muted">{{ $item->variant_label }}</span></span>
          <span>{{ money($item->line_total) }}</span>
        </div>
      @endforeach
      <div style="display:flex;justify-content:space-between;font-size:14.5px;margin-top:12px"><span class="muted">Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
      <div style="display:flex;justify-content:space-between;font-size:14.5px"><span class="muted">Delivery</span><span>{{ $order->shipping === 0 ? 'Free' : money($order->shipping) }}</span></div>
      <div style="display:flex;justify-content:space-between;border-top:1px solid var(--k-line);padding-top:12px;margin-top:8px;font-family:var(--font-display);font-weight:700;font-size:18px"><span>Total</span><span>{{ money($order->total) }}</span></div>
    </div>

    <div class="rows" style="margin-top:20px">
      <div><p class="k">Deliver to</p><p class="v">{{ $order->customerName() }} · {{ $order->address }}, {{ $order->city }}, {{ $order->district }}</p></div>
      <div><p class="k">Mobile</p><p class="v">{{ $order->phone }}</p></div>
      @if($order->note)<div><p class="k">Note</p><p class="v">{{ $order->note }}</p></div>@endif
    </div>
  </div>
  </div>
</x-layouts.store>
