{{-- My orders, from the prototype's html/account/index.html. --}}
<x-layouts.store title="My account" page="account">
  <div class="container account">
    <header class="account__head">
      <h1>Hi, <span data-greeting-name>{{ auth()->user()->name }}</span></h1>
      <p class="account__lede">Your orders, reviews and details in one place.</p>
    </header>
    <div class="account__layout">
      <x-store.account-nav active="orders" />
      <div class="account__content">
        <div data-orders-page>
          @if($orders->isNotEmpty())
            <div data-orders-filled>
              <h2 class="visually-hidden">My orders</h2>
              <ul class="order-cards" role="list" data-order-list>
                @foreach($orders as $order)
                  @php $count = $order->items->sum('qty'); @endphp
                  <li class="order-card">
                    <div class="order-card__head">
                      <p class="order-card__ref"><a href="{{ route('account.order', $order) }}">{{ $order->reference }}</a></p>
                      <p class="order-card__date">Placed {{ $order->created_at->format('j M Y') }}</p>
                    </div>
                    <div class="order-card__badges">
                      <span class="status status--{{ $order->status }}">{{ Str::ucfirst($order->status) }}</span>
                      <span class="status status--{{ $order->payment_status }}">{{ Str::ucfirst($order->payment_status) }}</span>
                    </div>
                    <div class="order-card__body">
                      <div class="order-card__items">
                        <span class="order-card__thumbs" aria-hidden="true">
                          @foreach($order->items->take(3) as $item)
                            <span class="media media--tile" data-placeholder style="--tone: var(--tone-{{ ($item->id % 4) + 1 }})"></span>
                          @endforeach
                        </span>
                        <span>{{ $count }} {{ Str::plural('item', $count) }}</span>
                      </div>
                      <p class="order-card__total">{{ money($order->total) }}</p>
                    </div>
                    <div class="order-card__actions">
                      <a class="link-arrow" href="{{ route('account.order', $order) }}">View order <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
                    </div>
                  </li>
                @endforeach
              </ul>
              @if($orders instanceof \Illuminate\Contracts\Pagination\Paginator)
                {{ $orders->links('vendor.pagination.kayaa') }}
              @endif
              <p class="account-note">
                <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg>
                <span data-orders-note>Orders placed as a guest with this email also appear here.</span>
              </p>
            </div>
          @else
            <div class="empty-state empty-state--card">
              <span class="empty-state__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-package"></use></svg></span>
              <h2 class="empty-state__title">No orders yet</h2>
              <a class="btn btn--primary" href="{{ route('shop.index') }}">Start shopping</a>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</x-layouts.store>
