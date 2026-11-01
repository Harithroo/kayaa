{{-- My reviews, from the prototype's html/account/reviews.html. A review waiting
     for moderation shows its status rather than disappearing. --}}
<x-layouts.store title="My reviews" page="account">
  <div class="container account">
    <header class="account__head">
      <h1>My reviews</h1>
      <p class="account__lede">Reviews you've written, and our replies.</p>
    </header>
    <div class="account__layout">
      <x-store.account-nav active="reviews" />
      <div class="account__content">
        @if($reviews->isNotEmpty())
          <ul class="review-items" role="list" data-review-list>
            @foreach($reviews as $review)
              <li class="review-card" data-review-item>
                <article>
                  <div class="review-card__product">
                    <x-store.photo class="media--tile" :image="$review->product?->primaryImage()" alt="" :seed="$review->product_id" ratio="1 / 1" />
                    <p class="review-card__product-name">
                      @if($review->product)<a href="{{ route('products.show', $review->product) }}">{{ $review->product->name }}</a>@endif
                    </p>
                  </div>
                  <header class="review-card__top">
                    <div class="review-card__head">
                      <x-store.stars :rating="$review->rating" :label="'Rated '.$review->rating.' out of 5'" />
                      <time class="review-card__date" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('j M Y') }}</time>
                      @if($review->is_verified_purchase)<span class="badge badge--outline">Verified purchase</span>@endif
                    </div>
                    <span class="status status--{{ $review->is_approved ? 'confirmed' : 'pending' }}">{{ $review->is_approved ? 'Published' : 'Awaiting approval' }}</span>
                  </header>
                  @if($review->title)<p class="review-card__headline">{{ $review->title }}</p>@endif
                  <p class="review-card__text">{{ $review->body }}</p>
                  @if($review->admin_reply)
                    <div class="review-card__reply">
                      <p class="review-card__reply-title">Reply from Kayaa</p>
                      <p>{{ $review->admin_reply }}</p>
                    </div>
                  @endif
                </article>
              </li>
            @endforeach
          </ul>
        @else
          <div class="empty-state empty-state--card">
            <span class="empty-state__icon"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg></span>
            <h2 class="empty-state__title">No reviews yet</h2>
            <p>Reviews you write appear here.</p>
            <a class="btn btn--primary" href="{{ route('shop.index') }}">Shop now</a>
          </div>
        @endif
      </div>
    </div>
  </div>
</x-layouts.store>
