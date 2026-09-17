<x-layouts.store :title="$title" :category="$category" nav="shop">
  <div class="section">
  <div class="wrap">
    @if($category)
      <p class="crumbs"><a href="{{ route('home') }}">Home</a> › <a href="{{ route('shop.index') }}">{{ $department->name }}</a> › {{ $category->name }}</p>
    @else
      <p class="eyebrow">Newborn – 2 years</p>
    @endif
    <h1 class="h2 page">{{ $title }}</h1>
    <p class="muted small" style="margin:0 0 18px">{{ $products->total() }} {{ Str::plural('item', $products->total()) }}</p>

    <div class="chips" style="margin-bottom:12px">
      <a class="chip {{ $activeSize ? '' : 'on' }}" href="{{ request()->fullUrlWithoutQuery(['size', 'page']) }}">All ages</a>
      @foreach($sizes as $size)
        <a class="chip {{ $activeSize === $size->label ? 'on' : '' }}" href="{{ request()->fullUrlWithQuery(['size' => $size->label, 'page' => null]) }}">{{ $size->label }}</a>
      @endforeach
    </div>

    <form class="toolbar" method="get">
      @foreach(request()->except('sort', 'page') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
      <select name="sort" aria-label="Sort" onchange="this.form.submit()">
        <option value="" @selected(!request('sort'))>Featured</option>
        <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
        <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
        <option value="new" @selected(request('sort') === 'new')>Newest</option>
      </select>
      <a class="btn line" href="{{ route('shop.index') }}">Clear filters</a>
    </form>

    @if($products->isEmpty())
      <div class="empty">
        <h3>Nothing in this filter yet</h3>
        <p>Try another age band or category.</p>
        <a class="btn dark sm" href="{{ route('shop.index') }}">Clear filters</a>
      </div>
    @else
      <div class="grid">
        @foreach($products as $product)<x-store.product-card :product="$product" />@endforeach
      </div>
      @if($products->hasPages())
        <nav class="pagination" aria-label="Pages">
          @if($products->onFirstPage())<span class="muted">‹</span>@else<a href="{{ $products->previousPageUrl() }}" rel="prev">‹</a>@endif
          @foreach($products->getUrlRange(1, $products->lastPage()) as $p => $url)
            @if($p === $products->currentPage())<span class="current">{{ $p }}</span>@else<a href="{{ $url }}">{{ $p }}</a>@endif
          @endforeach
          @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}" rel="next">›</a>@else<span class="muted">›</span>@endif
        </nav>
      @endif
    @endif
  </div>
  </div>
</x-layouts.store>
