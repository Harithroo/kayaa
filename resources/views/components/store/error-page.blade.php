@props(['code', 'icon' => 'i-circle-alert', 'heading', 'text' => null, 'search' => false])
{{-- The error pages, from the prototype's html/404.html and friends. They run on
     the error shell: wordmark and the short footer, nothing to click away into. --}}
<x-layouts.store :title="$heading" shell="error">
  <div class="container error-page">
    <div class="error-card">
      <span class="error-disc"><svg class="icon" aria-hidden="true" focusable="false"><use href="#{{ $icon }}"></use></svg></span>
      <p class="error-code">{{ $code }}</p>
      <h1>{{ $heading }}</h1>
      @if($text)<p class="error-text">{{ $text }}</p>@endif

      @if($search)
        <form class="error-search" role="search" aria-label="Search the shop" action="{{ route('search') }}" method="get" data-search="error">
          <label class="visually-hidden" for="error-q">Search Kayaa</label>
          <input id="error-q" type="search" name="q" placeholder="Search for clothes" autocomplete="off">
          <button type="submit" class="btn btn--primary">Search</button>
        </form>
        @php $cats = \App\Models\Category::query()->whereNull('parent_id')->where('is_active', true)->orderBy('position')->get(); @endphp
        @if($cats->isNotEmpty())
          <nav class="error-cats" aria-label="Browse categories">
            <p class="error-cats__label">Or browse a category</p>
            <ul class="chip-list" role="list">
              @foreach($cats as $cat)<li><a class="chip" href="{{ $cat->url() }}">{{ $cat->name }}</a></li>@endforeach
            </ul>
          </nav>
        @endif
      @endif

      <div class="error-actions">
        <a class="btn btn--primary btn--lg" href="{{ route('home') }}">Back to home</a>
        <a class="btn btn--secondary btn--lg" href="{{ route('shop.index', ['sort' => 'new']) }}">Shop new in</a>
      </div>
      <p class="error-help">Need a hand? <a href="{{ route('pages.contact') }}">Contact us</a></p>
    </div>
  </div>
</x-layouts.store>
