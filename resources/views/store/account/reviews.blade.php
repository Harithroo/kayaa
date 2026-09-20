<x-layouts.store title="My reviews" nav="account">
  <div class="section">
  <div class="wrap" style="max-width:760px">
    <h1 class="h2 page">My reviews</h1>
    <x-store.account-nav active="reviews" />

    @if($reviews->isEmpty())
      <div class="panel"><p style="margin:0">You haven't reviewed anything yet. Reviews help other parents pick sizes.</p></div>
    @else
      <div class="reviewlist">
        @foreach($reviews as $review)
          <article class="review">
            <div class="head">
              <x-store.stars :rating="$review->rating" />
              <span class="muted small">{{ $review->created_at->format('d M Y') }}</span>
              @unless($review->is_approved)<span class="pill pending">Awaiting approval</span>@endunless
            </div>
            <p class="who"><a href="{{ route('products.show', $review->product) }}">{{ $review->product->name }}</a></p>
            @if($review->title)<p class="t">{{ $review->title }}</p>@endif
            <p class="body">{{ $review->body }}</p>
            @if($review->admin_reply)<p class="reply"><strong>Kayaa:</strong> {{ $review->admin_reply }}</p>@endif
          </article>
        @endforeach
      </div>
    @endif
  </div>
  </div>
</x-layouts.store>
