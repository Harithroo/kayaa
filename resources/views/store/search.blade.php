{{-- Search results, from the prototype's html/search.html. Same listing furniture
     as the shop (toolbar, size help), with the suggestions dropdown on the form
     handled by search-suggest.js against GET /search/suggest. --}}
@php
  $sale = (bool) request('sale');
  $sort = request('sort', 'featured');
  $count = $results->count();
@endphp
<x-layouts.store title="Search" page="search">
  <div class="container listing" data-listing>

    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol data-breadcrumb>
        <li><a href="{{ route('home') }}">Home</a></li>
        <li aria-current="page">Search</li>
      </ol>
    </nav>

    <header class="listing-head">
      <h1 data-listing-title>{{ $q === '' ? 'Search' : 'Results for "'.$q.'"' }}</h1>
      <p class="listing-head__intro" data-listing-intro>
        {{ $q === '' ? 'Type what you are looking for.' : 'Products matching your search, across every category.' }}
      </p>
      <form class="listing-search" role="search" aria-label="Search the shop" action="{{ route('search') }}" method="get" data-search="page">
        <label class="visually-hidden" for="listing-q">Search Kayaa</label>
        <input id="listing-q" type="search" name="q" value="{{ $q }}" placeholder="Search for clothes" autocomplete="off">
        <button type="submit" class="btn btn--primary">Search</button>
      </form>
      @if($q !== '')
        <p class="listing-head__count" data-listing-count>{{ $count }} {{ Str::plural('product', $count) }}</p>
      @endif
    </header>

    @if($alsoMatches->isNotEmpty())
      <div class="also-matches" data-also-matches>
        <p class="also-matches__label" id="also-label">Also matches</p>
        <ul class="chip-list" role="list" aria-labelledby="also-label" data-also-list>
          @foreach($alsoMatches as $cat)
            <li><a class="chip" href="{{ $cat->url() }}">{{ $cat->name }}</a></li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($results->isNotEmpty())
      <form class="toolbar" method="get" action="{{ route('search') }}" data-listing-form>
        <input type="hidden" name="q" value="{{ $q }}">
        <p class="toolbar__count" data-listing-range>Showing 1-{{ $count }} of {{ $count }}</p>
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

      <div data-listing-results>
        <h2 class="visually-hidden">Products</h2>
        <ul class="product-grid" role="list" data-listing-grid>
          @foreach($results as $i => $product)
            <x-store.product-card :product="$product" :eager="$i < 4" />
          @endforeach
        </ul>
      </div>
    @elseif($q !== '')
      <section class="no-results" data-state="no-results" aria-labelledby="no-results-title">
        <h2 id="no-results-title" data-no-results-title>No results for "{{ $q }}"</h2>
        <ul class="no-results__tips" role="list">
          <li>Check the spelling.</li>
          <li>Try a more general word.</li>
          <li>Browse by age instead.</li>
        </ul>
        @if($navCategories->isNotEmpty())
          <p class="no-results__label">Browse by category</p>
          <ul class="chip-list" role="list">
            @foreach($navCategories as $cat)
              <li><a class="chip" href="{{ $cat->url() }}">{{ $cat->name }}</a></li>
            @endforeach
          </ul>
        @endif
        @if($navSizes->isNotEmpty())
          <p class="no-results__label">Browse by age</p>
          <ul class="chip-list" role="list">
            @foreach($navSizes as $size)
              <li><a class="chip" href="{{ route('shop.index', ['size' => $size->label]) }}">{{ $size->label }}</a></li>
            @endforeach
          </ul>
        @endif
      </section>
    @else
      @if($navCategories->isNotEmpty())
        <nav class="age-chips" aria-label="Browse categories">
          <ul class="age-chips__list" role="list">
            @foreach($navCategories as $cat)
              <li><a class="chip" href="{{ $cat->url() }}">{{ $cat->name }}</a></li>
            @endforeach
          </ul>
        </nav>
      @endif
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
