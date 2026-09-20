<x-layouts.store title="Wishlist" nav="wishlist" meta-description="Products you've saved at Kayaa.">
  <div class="wrap section pad">
    <h1 class="h2 page">Wishlist</h1>
    <p class="muted" style="margin:0 0 20px;font-size:14.5px" data-wish-note>
      @if($products->isEmpty())
        Tap the heart on a product to save it.
      @else
        {{ $products->count() }} {{ Str::plural('item', $products->count()) }} saved{{ auth()->check() ? ' to your account' : ' on this device' }}.
      @endif
    </p>

    @if($products->isEmpty())
      <div class="empty">
        <h3>Nothing saved yet</h3>
        <p>Tap the heart on any product to keep it here.</p>
        <a class="btn sm" href="{{ route('shop.index') }}">Browse products</a>
      </div>
    @else
      <div class="grid" data-wish-grid>
        @foreach($products as $product)
          <x-store.product-card :product="$product">
            @if($product->totalStock() > 0)
              <a class="btn dark sm cardcta" href="{{ route('products.show', $product) }}">Choose size</a>
            @else
              <span class="btn line sm cardcta" aria-disabled="true">Sold out</span>
            @endif
          </x-store.product-card>
        @endforeach
      </div>
      @guest
        <p class="muted small" style="margin:26px 0 0">Your wishlist is kept in this browser. <a href="{{ route('account.login') }}">Sign in</a> or <a href="{{ route('account.register') }}">create an account</a> to keep it on every device.</p>
      @endguest
    @endif
  </div>
</x-layouts.store>
