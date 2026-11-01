{{-- All categories, from the prototype's html/categories.html. One grid per
     department, with the product count the controller already works out. --}}
<x-layouts.store title="All categories" page="categories"
  metaDescription="Every Kayaa category for babies: bodysuits, sleepwear, sets, outerwear and more.">
  <div class="container categories">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="{{ route('home') }}">Home</a></li>
        <li aria-current="page">Categories</li>
      </ol>
    </nav>
    <h1>All categories</h1>
    <p class="categories__lede">Everything Kayaa makes for babies, by category.</p>

    @foreach($departments as $department)
      @if($departments->count() > 1)
        <h2 class="section__head">{{ $department->name }}</h2>
      @endif
      <ul class="cat-grid" role="list">
        @foreach($department->categories as $cat)
          <li>
            <a class="cat-tile" href="{{ $cat->url() }}">
              <x-store.photo :image="null" alt="" :seed="$cat->id" ratio="4 / 3" />
              <span class="cat-tile__label">{{ $cat->name }} <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></span>
              <span class="cat-tile__count">{{ $cat->product_count ?? 0 }} {{ Str::plural('item', $cat->product_count ?? 0) }}</span>
            </a>
          </li>
        @endforeach
      </ul>
    @endforeach
  </div>
</x-layouts.store>
