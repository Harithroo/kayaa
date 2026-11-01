{{-- Shop and category listings, from the prototype's html/shop.html and
     html/category.html (one template: the two differ only in the head and the
     breadcrumb). Filtering and sorting stay on the server; the toolbar's
     [data-autosubmit] controls just submit the form. --}}
@php
  $isCategory = (bool) $category;
  $sale = (bool) request('sale');
  $sort = request('sort', 'featured');
  $total = $products->total();
@endphp
<x-layouts.store :title="$title" :page="$isCategory ? 'category shop' : 'shop'"
  :metaDescription="$category?->description">

  <div class="container listing" data-listing>

    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol data-breadcrumb>
        <li><a href="{{ route('home') }}">Home</a></li>
        @if($isCategory)
          <li><a href="{{ route('shop.index') }}">Shop</a></li>
          @if($category->parent)<li><a href="{{ $category->parent->url() }}">{{ $category->parent->name }}</a></li>@endif
          <li aria-current="page">{{ $category->name }}</li>
        @else
          <li aria-current="page">{{ $sale ? 'Sale' : 'Shop' }}</li>
        @endif
      </ol>
    </nav>

    <header class="listing-head">
      <h1 data-listing-title>{{ $isCategory ? $category->name : ($collection?->name ?? ($sale ? 'Sale' : 'All baby clothing')) }}</h1>
      <p class="listing-head__intro" data-listing-intro>
        @if($isCategory)
          {{ $category->description ?: 'Soft '.Str::lower($category->name).' for babies, sized by age.' }}
        @else
          Soft everyday essentials, sorted by age and stage.
        @endif
      </p>
      <p class="listing-head__count" data-listing-count>{{ $total }} {{ Str::plural('product', $total) }}</p>
    </header>

    @if($isCategory && $category->children->where('is_active', true)->isNotEmpty())
      <nav class="age-chips" aria-label="{{ $category->name }} types">
        <ul class="age-chips__list" role="list">
          <li><a class="chip is-selected" href="{{ $category->url() }}" aria-current="page">All {{ Str::lower($category->name) }}</a></li>
          @foreach($category->children->where('is_active', true) as $child)
            <li><a class="chip" href="{{ $child->url() }}">{{ $child->name }}</a></li>
          @endforeach
        </ul>
      </nav>
    @elseif($sizes->isNotEmpty())
      <nav class="age-chips" aria-label="Filter by size">
        <ul class="age-chips__list" role="list">
          <li><a @class(['chip', 'is-selected' => ! $activeSize]) href="{{ route('shop.index', array_filter(['sale' => $sale ? 1 : null, 'sort' => $sort !== 'featured' ? $sort : null])) }}" @unless($activeSize) aria-current="page" @endunless>All sizes</a></li>
          @foreach($sizes as $size)
            <li><a @class(['chip', 'is-selected' => $activeSize === $size->label])
                   href="{{ route('shop.index', array_filter(['size' => $size->label, 'sale' => $sale ? 1 : null, 'sort' => $sort !== 'featured' ? $sort : null])) }}"
                   @if($activeSize === $size->label) aria-current="page" @endif>{{ $size->label }}</a></li>
          @endforeach
        </ul>
      </nav>
    @endif

    <form class="toolbar" method="get" action="{{ $isCategory ? $category->url() : route('shop.index') }}" data-listing-form>
      {{-- Keep whatever else is filtering the list when the sort or the sale switch changes. --}}
      @if($activeSize)<input type="hidden" name="size" value="{{ $activeSize }}">@endif
      @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
      @if(request('tag'))<input type="hidden" name="tag" value="{{ request('tag') }}">@endif
      <p class="toolbar__count" data-listing-range>
        @if($total)Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $total }}@endif
      </p>
      <div class="toolbar__controls">
        <div class="field toolbar__sort">
          <label for="sort">Sort by</label>
          <select id="sort" name="sort" data-autosubmit>
            <option value="featured" @selected($sort === 'featured')>Featured</option>
            <option value="new" @selected($sort === 'new')>Newest</option>
            <option value="price_asc" @selected($sort === 'price_asc')>Price: low to high</option>
            <option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option>
          </select>
        </div>
        <label class="switch">
          <input type="checkbox" role="switch" name="sale" value="1" data-autosubmit @checked($sale)>
          <span class="switch__track" aria-hidden="true"></span>
          <span class="switch__label">Sale only</span>
        </label>
        <noscript><button type="submit" class="btn btn--secondary btn--sm">Apply</button></noscript>
      </div>
    </form>

    @if($products->isNotEmpty())
      <div data-listing-results>
        <h2 class="visually-hidden">Products</h2>
        <ul class="product-grid" role="list" data-listing-grid>
          @foreach($products as $i => $product)
            <x-store.product-card :product="$product" :eager="$i < 4" />
          @endforeach
        </ul>
        {{ $products->links('vendor.pagination.kayaa') }}
      </div>
    @else
      <div class="empty-state listing-empty">
        <span class="empty-state__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-package"></use></svg></span>
        <h2 class="empty-state__title">No products match these filters</h2>
        <p class="listing-empty__text">Try removing a filter to see more.</p>
        <a class="btn btn--primary" href="{{ route('shop.index') }}" data-clear-filters>Clear filters</a>
      </div>
    @endif

    <aside class="size-help" aria-labelledby="size-help-title">
      <span class="size-help__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-ruler"></use></svg></span>
      <div class="size-help__text">
        <h2 class="size-help__title" id="size-help-title">Not sure about the size?</h2>
        <p>Our size guide shows what fits each age band; height and weight are the better guide.</p>
      </div>
      <a class="link-arrow" href="{{ route('pages.size-guide') }}">Open the size guide <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
    </aside>

  </div>
</x-layouts.store>
