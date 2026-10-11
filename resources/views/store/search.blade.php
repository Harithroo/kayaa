{{-- Search results, from the prototype's html/search.html. The suggestions
     dropdown on this form (and the header's) is search-suggest.js, which calls
     GET /search/suggest. ?focus=1 focuses the field: the phone's Search tab. --}}
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
      @if($q === '')
        <p class="listing-head__intro" data-listing-intro>Type what you are looking for.</p>
      @endif
      <form class="listing-search" role="search" aria-label="Search the shop" action="{{ route('search') }}" method="get" data-search="page">
        <label class="visually-hidden" for="listing-q">Search Kayaa</label>
        <input id="listing-q" type="search" name="q" value="{{ $q }}" placeholder="Search for clothes" autocomplete="off">
        <button type="submit" class="btn btn--primary">Search</button>
      </form>
      @if($q !== '')
        <p class="listing-head__count" data-listing-count>{{ $results->count() }} {{ Str::plural('product', $results->count()) }}</p>
      @endif
    </header>

    @if($results->isNotEmpty())
      <div data-listing-results>
        <h2 class="visually-hidden">Products</h2>
        <ul class="product-grid" role="list" data-listing-grid>
          @foreach($results as $i => $product)
            <x-store.product-card :product="$product" :eager="$i < 4" />
          @endforeach
        </ul>
      </div>
    @elseif($q !== '')
      <div class="empty-state listing-empty">
        <span class="empty-state__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-search-x"></use></svg></span>
        <h2 class="empty-state__title">No results for "{{ $q }}"</h2>
        <p class="listing-empty__text">Check the spelling, or browse by category instead.</p>
        <a class="btn btn--primary" href="{{ route('shop.index') }}">Shop all</a>
      </div>
    @endif

    @if($q === '' && $navCategories->isNotEmpty())
      <nav class="age-chips" aria-label="Browse categories">
        <ul class="age-chips__list" role="list">
          @foreach($navCategories as $cat)
            <li><a class="chip" href="{{ $cat->url() }}">{{ $cat->name }}</a></li>
          @endforeach
        </ul>
      </nav>
    @endif
  </div>
</x-layouts.store>
