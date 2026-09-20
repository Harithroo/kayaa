<x-layouts.store title="My account" nav="account">
  <div class="section">
  <div class="wrap" style="max-width:760px">
    <h1 class="h2 page">Hello, {{ Str::before(auth()->user()->name, ' ') }}</h1>
    <x-store.account-nav active="orders" />

    @if($orders->isEmpty())
      <div class="panel" style="text-align:center">
        <p style="margin:0 0 14px">No orders yet.</p>
        <a class="btn" href="{{ route('shop.index') }}">Start shopping</a>
      </div>
    @else
      <div class="orderlist">
        @foreach($orders as $order)
          <a class="orderrow" href="{{ route('account.order', $order) }}">
            <span>
              <strong>{{ $order->reference }}</strong>
              <span class="muted small">{{ $order->created_at->format('d M Y') }} · {{ $order->items->sum('qty') }} items</span>
            </span>
            <span class="right">
              <span class="pill {{ $order->status }}">{{ ucfirst($order->status) }}</span>
              <span class="muted small">{{ money($order->total) }}</span>
            </span>
          </a>
        @endforeach
      </div>
      <div style="margin-top:18px">{{ $orders->links() }}</div>
    @endif
  </div>
  </div>
</x-layouts.store>
