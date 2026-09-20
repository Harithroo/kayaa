<x-layouts.store title="All categories" nav="shop" meta-description="Browse Kayaa baby clothing by category — bodysuits, sleepwear, sets and more.">
  @php $tints = ['butter','sage','blush','sand','cloud']; $i = 0; @endphp
  <div class="wrap section pad">
    <p class="crumbs"><a href="{{ route('home') }}">Home</a> › Categories</p>
    <h1 class="h2 page">Shop by category</h1>

    @forelse($departments as $department)
      @continue($department->categories->isEmpty())
      @if($departments->count() > 1)<h2 class="h3 dept">{{ $department->name }}</h2>@endif

      <div class="catgrid">
        @foreach($department->categories as $category)
          <div class="catcard">
            <a class="cattile t-{{ $tints[$i++ % 5] }}" href="{{ $category->url() }}">
              <span class="catname">{{ $category->name }}</span>
              <span class="catcount">{{ $category->product_count }} {{ Str::plural('item', $category->product_count) }}</span>
            </a>
            @if($category->description)<p class="catdesc">{{ $category->description }}</p>@endif
            @if($category->children->isNotEmpty())
              <div class="subcats">
                @foreach($category->children as $child)<a class="chip sm" href="{{ $child->url() }}">{{ $child->name }}</a>@endforeach
              </div>
            @endif
          </div>
        @endforeach
        <div class="catcard">
          <a class="cattile t-butter" href="{{ route('shop.index', ['sale' => 1]) }}">
            <span class="catname sale">Sale</span>
            <span class="catcount">Reduced prices</span>
          </a>
        </div>
      </div>
    @empty
      <div class="empty">
        <h3>No categories yet</h3>
        <p>Everything is still in one place for now.</p>
        <a class="btn dark sm" href="{{ route('shop.index') }}">Shop all</a>
      </div>
    @endforelse

    <p style="margin:28px 0 0"><a class="btn line" href="{{ route('shop.index') }}">Shop everything</a></p>
  </div>
</x-layouts.store>
