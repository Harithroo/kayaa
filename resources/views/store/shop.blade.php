<x-layouts.store :title="$title" :category="$category">
  <div class="wrap section">
    @if($category)
      <p class="crumbs"><a href="{{ route('home') }}">Home</a> › <a href="{{ route('shop.index') }}">{{ $department->name }}</a> › {{ $category->name }}</p>
    @else
      <p class="eyebrow">Newborn – 2 years</p>
    @endif
    <div class="sechead"><h1 class="sec">{{ $title }}</h1></div>
    @if($category?->description)<p class="muted" style="margin:0 0 14px;max-width:60ch">{{ $category->description }}</p>@endif

    <div class="ages" style="margin-bottom:14px">
      <a class="age {{ $activeSize ? '' : 'on' }}" href="{{ request()->fullUrlWithoutQuery(['size', 'page']) }}">All ages</a>
      @foreach($sizes as $size)
        <a class="age {{ $activeSize === $size->label ? 'on' : '' }}" href="{{ request()->fullUrlWithQuery(['size' => $size->label, 'page' => null]) }}">{{ $size->label }}</a>
      @endforeach
    </div>

    <form class="toolbar" method="get">
      @foreach(request()->except('sort', 'page') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
      <span class="muted">{{ $products->total() }} {{ Str::plural('item', $products->total()) }}</span>
      <label>Sort
        <select name="sort" onchange="this.form.submit()">
          <option value="" @selected(!request('sort'))>Newest</option>
          <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
          <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
        </select>
      </label>
    </form>

    @if($products->isEmpty())
      <p class="empty">Nothing here yet{{ $activeSize ? " in $activeSize" : '' }}. <a href="{{ route('shop.index') }}">See everything</a>.</p>
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
</x-layouts.store>
