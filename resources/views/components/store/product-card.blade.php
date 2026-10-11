@props(['product', 'eager' => false])
{{-- The design system's product card (prototype: the `product-grid` items on
     shop.html). Badge priority is out of stock, then sale, then new; an out-of-stock
     card has no quick add. The <li> keeps the prototype's data-* attributes: the
     quick-add sheet reads them, and they are handy hooks for anything client-side. --}}
@php
  $price = $product->minPrice();
  $was = $product->compareAtPrice();
  $onSale = $was !== null && $was > $price;
  $inStock = $product->totalStock() > 0;
  $colors = $product->colors();
  $sizes = $product->sizeOptions();
  $meta = array_values(array_filter([
    $colors->count() > 1 ? $colors->count().' colours' : ($colors->count() === 1 ? $colors->first()->name : null),
    $product->sizeRangeLabel(),
  ]));
@endphp
<li data-product
    data-slug="{{ $product->slug }}"
    data-name="{{ $product->name }}"
    data-price="{{ (int) ($price / 100) }}"
    data-category="{{ $product->category?->slug }}"
    data-new="{{ $product->is_new ? 1 : 0 }}"
    data-sale="{{ $onSale ? 1 : 0 }}"
    data-stock="{{ $product->totalStock() }}">
  <article @class(['product-card', 'product-card--oos' => ! $inStock])>
    <div class="product-card__media">
      <x-store.photo :image="$product->primaryImage()" :alt="$product->name" :seed="$product->id" :eager="$eager" />
      @if(! $inStock)
        <span class="badge badge--muted product-card__badge">Out of stock</span>
      @elseif($onSale)
        <span class="badge badge--accent product-card__badge">Sale</span>
      @elseif($product->is_new)
        <span class="badge badge--lilac product-card__badge">New</span>
      @endif
      @if($inStock && $sizes->isNotEmpty())
        <button type="button" class="product-card__quick" data-quick-add
                data-product="{{ $product->id }}"
                data-slug="{{ $product->slug }}"
                data-name="{{ $product->name }}"
                data-price="{{ (int) ($price / 100) }}"
                @if($onSale) data-was="{{ (int) ($was / 100) }}" @endif
                data-tone="{{ ($product->id % 4) + 1 }}"
                data-sizes="{{ $sizes->pluck('id')->join(' ') }}"
                data-colours="{{ $colors->pluck('id')->join(' ') }}"
                aria-haspopup="dialog" aria-label="Quick add, {{ $product->name }}">
          <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-plus"></use></svg>
          <span class="product-card__quick-label">Quick add</span>
        </button>
      @endif
    </div>
    <div class="product-card__info">
      <h3 class="product-card__name"><a class="product-card__link" href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
      @if($meta)<p class="product-card__meta">{{ implode(' · ', $meta) }}</p>@endif
      <p @class(['price', 'price--sale' => $onSale])>
        @if($onSale)<span class="visually-hidden">Sale price </span>@endif
        <span class="price__now">{{ money($price) }}</span>
        @if($onSale) <s class="price__was"><span class="visually-hidden">Was </span>{{ money($was) }}</s>@endif
      </p>
    </div>
    {{ $slot }}
  </article>
</li>
