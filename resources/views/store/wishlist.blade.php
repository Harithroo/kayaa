{{-- Wishlist, from the prototype's html/wishlist.html. The prototype kept saved
     products in sessionStorage and hid the rest; here the server returns the
     saved products, and each card gets the page's own Remove button. --}}
<x-layouts.store title="Your wishlist" page="wishlist">
  <div class="container wishlist" data-wishlist-page>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="{{ route('home') }}">Home</a></li>
        <li aria-current="page">Wishlist</li>
      </ol>
    </nav>
    <h1>Your wishlist</h1>
    <p class="wishlist__lede" data-wishlist-count>
      @if($products->isEmpty())Products you save are kept here.@else{{ $products->count() }} {{ Str::plural('product', $products->count()) }} saved.@endif
    </p>

    @if($products->isNotEmpty())
      <div data-wishlist-filled>
        <h2 class="visually-hidden">Saved products</h2>
        <ul class="product-grid" role="list" data-wishlist-grid>
          @foreach($products as $i => $product)
            <x-store.product-card :product="$product" :eager="$i < 4">
              <div class="wishlist-actions">
                <a class="btn btn--primary btn--sm" href="{{ route('products.show', $product) }}">Choose size</a>
                <form method="post" action="{{ route('wishlist.toggle', $product) }}">
                  @csrf
                  <button type="submit" class="btn btn--secondary btn--sm">Remove<span class="visually-hidden"> {{ $product->name }}</span></button>
                </form>
              </div>
            </x-store.product-card>
          @endforeach
        </ul>
      </div>
    @else
      <div class="empty-state wishlist-empty">
        <span class="empty-state__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-heart"></use></svg></span>
        <h2 class="empty-state__title" tabindex="-1">Nothing saved yet.</h2>
        <p class="listing-empty__text">Tap Save on a product to keep it here.</p>
        <a class="btn btn--primary" href="{{ route('shop.index') }}">Shop now</a>
      </div>
    @endif
  </div>
</x-layouts.store>
