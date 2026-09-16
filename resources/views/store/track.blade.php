<x-layouts.store title="Track your order">
  <div class="wrap section" style="max-width:560px">
    <div class="sechead"><h1 class="sec">Track your order</h1></div>
    <form method="post" action="{{ route('orders.track') }}">
      @csrf
      <x-store.field name="reference" label="Order number" placeholder="KY-260917-AB12" required />
      <x-store.field name="phone" label="Mobile number used on the order" type="tel" inputmode="tel" required />
      <button class="btn dark">Find my order</button>
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
          <ul style="list-style:none;padding:0;margin:0 0 14px;display:grid;gap:8px">
            @foreach($steps as $key => $label)
              @php $done = $idx !== false && array_search($key, array_keys($steps), true) <= $idx; @endphp
              <li style="display:flex;gap:10px;align-items:center;{{ $done ? '' : 'color:var(--ink-soft)' }}">
                <span style="width:10px;height:10px;border-radius:50%;background:{{ $done ? 'var(--teal)' : 'var(--line)' }}"></span>{{ $label }}
                @if($key === 'shipped' && $order->shipped_at)<span class="muted" style="font-size:13px">· {{ $order->shipped_at->format('d M') }}</span>@endif
              </li>
            @endforeach
          </ul>
        @endif
        <p class="muted" style="font-size:13.5px;margin:0">Placed {{ $order->created_at->format('d M Y') }} · {{ $order->items->sum('qty') }} items · {{ money($order->total) }} · {{ $order->payment_method === 'cod' ? 'Cash on delivery' : 'Paid online' }}</p>
      </div>
    @endif
  </div>
</x-layouts.store>
