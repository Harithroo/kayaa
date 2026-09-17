<x-layouts.store title="Search" nav="search">
  <div class="wrap" style="padding:26px 18px">
    <form class="searchbox" method="get" action="{{ route('search') }}" role="search">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" name="q" value="{{ $q }}" placeholder="Search bodysuits, sleep bags, gift sets…" autofocus aria-label="Search">
      @if($q)<a href="{{ route('search') }}" aria-label="Clear" style="padding:8px;color:var(--k-ink-2)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6 6 18"/></svg></a>@endif
    </form>

    @if(!$q)
      <p class="eyebrow muted" style="margin-bottom:10px">Popular searches</p>
      <div class="chips" style="flex-wrap:wrap;margin-bottom:26px">
        @foreach(['Bodysuits', 'Sleep bag', 'Newborn', 'Bamboo', 'Kurta set', 'Bibs'] as $s)
          <a class="chip" href="{{ route('search', ['q' => $s]) }}">{{ $s }}</a>
        @endforeach
      </div>
    @else
      <p class="muted small" style="margin:0 0 16px">
        @if($results->isEmpty())No matches for “{{ $q }}”. Try “bodysuit” or “sleep”.@else{{ $results->count() }} {{ Str::plural('result', $results->count()) }} for “{{ $q }}”@endif
      </p>
    @endif

    @if($results->isNotEmpty())
      <div class="grid">
        @foreach($results as $product)<x-store.product-card :product="$product" />@endforeach
      </div>
    @endif
  </div>
</x-layouts.store>
