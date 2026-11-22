{{-- Shop and category listings, from the prototype's html/shop.html and
     html/category.html (one template: the two differ only in the head and the
     breadcrumb), with a filter panel (App\Support\ShopFilters): a sidebar from
     900px, a drawer below it. Everything is a plain GET form, so it all works
     without scripts; shop-filters.js only submits on change (desktop) and turns
     the panel into a drawer (phones). --}}
@php
  $isCategory = (bool) $category;
  $sort = request('sort', 'featured');
  $total = $products->total();
  $active = $filters->count();
  $check = '<svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg>';
  $sortQuery = $sort !== 'featured' ? ['sort' => $sort] : [];
@endphp
<x-layouts.store :page="$isCategory ? 'category shop' : 'shop'" :meta="$meta" :json-ld="$jsonLd">

  <div class="container listing" data-listing>

    {{-- One trail, used for both the visible nav and the BreadcrumbList. --}}
    <x-store.breadcrumb :trail="$breadcrumbs" />

    <header class="listing-head">
      <h1 data-listing-title>{{ $isCategory ? $category->name : ($collection?->name ?? ($filters->sale && $active === 1 ? 'Sale' : 'All baby clothing')) }}</h1>
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
    @endif

    <div class="shop-layout">

      {{-- The filter panel. Below 900px shop-filters.js makes it a drawer (data-layer="filters", opened by the Filters button). --}}
      <form class="filters" id="filters" method="get" action="{{ $base }}" data-layer="filters" data-shop-filters aria-labelledby="filters-title">
        @if($collection)<input type="hidden" name="tag" value="{{ $collection->slug }}">@endif

        <div class="filters__head">
          <h2 class="filters__title" id="filters-title">Filters @if($active)<span class="filters__badge">{{ $active }}</span>@endif</h2>
          <button type="button" class="icon-btn icon-btn--plain filters__close" data-close-layer aria-label="Close filters" hidden>
            <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-x"></use></svg>
          </button>
        </div>

        <div class="filters__body">
          @if($facets['categories']->isNotEmpty())
            <details class="filter-group" open>
              <summary class="filter-group__title"><span>Category</span><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <fieldset class="filter-group__body">
                <legend class="visually-hidden">Category</legend>
                <ul class="filter-list" role="list">
                  @foreach($facets['categories'] as $o)
                    <li>
                      <label class="checkbox filter-option">
                        <input type="checkbox" name="category[]" value="{{ $o->value }}" data-filter-auto @checked($o->checked) @disabled(! $o->count && ! $o->checked)>
                        <span class="checkbox__box" aria-hidden="true">{!! $check !!}</span>
                        <span class="filter-option__label">{{ $o->label }}</span>
                        <span class="filter-option__count">{{ $o->count }}<span class="visually-hidden"> {{ Str::plural('product', $o->count) }}</span></span>
                      </label>
                    </li>
                  @endforeach
                </ul>
              </fieldset>
            </details>
          @endif

          @if($facets['sizes']->isNotEmpty())
            <details class="filter-group" open>
              <summary class="filter-group__title"><span>Size</span><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <fieldset class="filter-group__body">
                <legend class="visually-hidden">Size</legend>
                <ul class="filter-list filter-list--cols" role="list">
                  @foreach($facets['sizes'] as $o)
                    <li>
                      <label class="checkbox filter-option">
                        <input type="checkbox" name="size[]" value="{{ $o->value }}" data-filter-auto @checked($o->checked) @disabled(! $o->count && ! $o->checked)>
                        <span class="checkbox__box" aria-hidden="true">{!! $check !!}</span>
                        <span class="filter-option__label">{{ $o->label }}</span>
                        <span class="filter-option__count">{{ $o->count }}<span class="visually-hidden"> {{ Str::plural('product', $o->count) }}</span></span>
                      </label>
                    </li>
                  @endforeach
                </ul>
                <a class="filter-group__link" href="{{ route('pages.size-guide') }}">Size guide</a>
              </fieldset>
            </details>
          @endif

          @if($facets['colours']->isNotEmpty())
            <details class="filter-group" open>
              <summary class="filter-group__title"><span>Colour</span><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <fieldset class="filter-group__body">
                <legend class="visually-hidden">Colour</legend>
                <ul class="filter-list" role="list">
                  @foreach($facets['colours'] as $o)
                    <li>
                      <label class="checkbox filter-option">
                        <input type="checkbox" name="colour[]" value="{{ $o->value }}" data-filter-auto @checked($o->checked) @disabled(! $o->count && ! $o->checked)>
                        <span class="checkbox__box" aria-hidden="true">{!! $check !!}</span>
                        <span class="dot filter-option__dot" @if($o->hex) style="background: {{ $o->hex }}" @endif aria-hidden="true"></span>
                        <span class="filter-option__label">{{ $o->label }}</span>
                        <span class="filter-option__count">{{ $o->count }}<span class="visually-hidden"> {{ Str::plural('product', $o->count) }}</span></span>
                      </label>
                    </li>
                  @endforeach
                </ul>
              </fieldset>
            </details>
          @endif

          @if($facets['price']->lo !== null)
            <details class="filter-group" open>
              <summary class="filter-group__title"><span>Price</span><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
              <fieldset class="filter-group__body">
                <legend class="visually-hidden">Price in rupees</legend>
                <div class="filter-price">
                  <div class="field">
                    <label for="filter-min">Min (Rs.)</label>
                    <input id="filter-min" type="number" name="min" inputmode="numeric" min="0" step="50" value="{{ $filters->min }}" placeholder="{{ number_format($facets['price']->lo) }}" data-filter-auto>
                  </div>
                  <span class="filter-price__to" aria-hidden="true">–</span>
                  <div class="field">
                    <label for="filter-max">Max (Rs.)</label>
                    <input id="filter-max" type="number" name="max" inputmode="numeric" min="0" step="50" value="{{ $filters->max }}" placeholder="{{ number_format($facets['price']->hi) }}" data-filter-auto>
                  </div>
                </div>
              </fieldset>
            </details>
          @endif

          <details class="filter-group" open>
            <summary class="filter-group__title"><span>Show only</span><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
            <fieldset class="filter-group__body">
              <legend class="visually-hidden">Show only</legend>
              <ul class="filter-list" role="list">
                @foreach([['stock', 'In stock', $filters->stock], ['new', 'New arrivals', $filters->new], ['sale', 'On sale', $filters->sale]] as [$name, $label, $on])
                  <li>
                    <label class="checkbox filter-option">
                      <input type="checkbox" name="{{ $name }}" value="1" data-filter-auto @checked($on) @disabled(! $facets[$name] && ! $on)>
                      <span class="checkbox__box" aria-hidden="true">{!! $check !!}</span>
                      <span class="filter-option__label">{{ $label }}</span>
                      <span class="filter-option__count">{{ $facets[$name] }}<span class="visually-hidden"> {{ Str::plural('product', $facets[$name]) }}</span></span>
                    </label>
                  </li>
                @endforeach
              </ul>
            </fieldset>
          </details>
        </div>

        <div class="filters__foot">
          @if($active)<a class="btn btn--secondary filters__clear" href="{{ $base.($sortQuery ? '?'.http_build_query($sortQuery) : '') }}">Clear all</a>@endif
          <button type="submit" class="btn btn--primary filters__apply">Show results</button>
        </div>
      </form>

      <div class="shop-results">
        <div class="toolbar">
          <p class="toolbar__count" data-listing-range>
            @if($total)Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $total }}@endif
          </p>
          <div class="toolbar__controls">
            <button type="button" class="btn btn--secondary btn--sm toolbar__filters" data-open-layer="filters" aria-haspopup="dialog" aria-controls="filters" aria-expanded="false" hidden>
              <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-sliders-horizontal"></use></svg>
              Filters @if($active)<span class="filters__badge">{{ $active }}</span>@endif
            </button>
            <div class="field toolbar__sort">
              <label for="sort">Sort by</label>
              <select id="sort" name="sort" form="filters" data-autosubmit>
                <option value="featured" @selected($sort === 'featured')>Featured</option>
                <option value="new" @selected($sort === 'new')>Newest</option>
                <option value="price_asc" @selected($sort === 'price_asc')>Price: low to high</option>
                <option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option>
              </select>
            </div>
            <noscript><button type="submit" form="filters" class="btn btn--secondary btn--sm">Apply</button></noscript>
          </div>
        </div>

        @if($chips)
          <div class="active-filters">
            <h2 class="visually-hidden">Active filters</h2>
            <ul class="active-filters__list" role="list">
              @foreach($chips as $chip)
                <li><a class="chip active-filters__chip" href="{{ $chip['url'] }}" aria-label="Remove filter: {{ $chip['label'] }}">{{ $chip['label'] }} <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-x"></use></svg></a></li>
              @endforeach
              <li><a class="active-filters__clear" href="{{ $base.($sortQuery ? '?'.http_build_query($sortQuery) : '') }}">Clear all</a></li>
            </ul>
          </div>
        @endif

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
            <a class="btn btn--primary" href="{{ $base }}" data-clear-filters>Clear filters</a>
          </div>
        @endif
      </div>
    </div>

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
