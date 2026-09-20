@php
  $colors = $product->colors();
  $sizes = $product->sizeOptions();
  $firstColorId = $colors->first()?->id ?? 0;
  $defaultSize = collect($matrix[$firstColorId] ?? [])->filter(fn ($v) => $v['stock'] > 0)->keys()->first();
  $images = $product->images;
  $main = $images->first();
  $tints = ['butter','sage','cloud','blush','sand'];
  $tint = $tints[$product->id % 5];
  $anyStock = $product->totalStock() > 0;
  $ratingAvg = $product->ratingAverage();
  $ratingCount = $product->ratingCount();
@endphp
<x-layouts.store :title="$product->name" :metaDescription="Str::limit($product->description, 150)" :category="$product->category" nav="shop">
  <div class="wrap" style="padding:22px 18px 30px">
    <a class="back" href="{{ $product->category->url() }}">← Back to {{ Str::lower($product->category->name) }}</a>
    <div class="pdp">
      <div class="gallery" data-gallery>
        <x-store.photo class="main" :image="$main" :tint="$tint" :alt="$product->name" cap="product shot — front">
          @if($main)<img data-gallery-main src="{{ $main->url() }}" alt="{{ $main->alt ?? $product->name }}">@endif
        </x-store.photo>
        @if($images->count() > 1)
          <div class="thumbs">
            @foreach($images as $img)
              <button type="button" class="ph" data-thumb="{{ $img->url() }}" aria-label="Photo {{ $loop->iteration }}"><img src="{{ $img->url() }}" alt="{{ $img->alt ?? '' }}" loading="lazy"></button>
            @endforeach
          </div>
        @endif
      </div>

      <div>
        <form method="post" action="{{ route('cart.add') }}" data-variant-picker data-matrix='@json($matrix)'>
          @csrf
          <input type="hidden" name="variant_id" value="">
          @if($product->collection_label)<p class="eyebrow">{{ $product->collection_label }}</p>@endif
          <h1>{{ $product->name }}</h1>
          @if($ratingCount > 0)
            <p class="ratingline"><x-store.stars :rating="$ratingAvg" />
              <a href="#reviews">{{ number_format($ratingAvg, 1) }} · {{ $ratingCount }} {{ Str::plural('review', $ratingCount) }}</a>
            </p>
          @endif
          <p class="pricerow"><span class="price" data-price>{{ money($product->minPrice()) }}</span><span class="was" data-was hidden></span></p>

          @if($colors->isNotEmpty())
            <p class="label">Colour — <span data-color-name>{{ $colors->first()->name }}</span></p>
            <div class="swatches">
              @foreach($colors as $color)
                <button type="button" class="sw {{ $loop->first ? 'on' : '' }}" style="background:{{ $color->hex }}" aria-label="{{ $color->name }}" data-color="{{ $color->id }}"></button>
              @endforeach
            </div>
          @endif

          <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:9px">
            <p class="label" style="margin:0">Size</p>
            <button type="button" data-toggle-sizeguide style="background:none;border:0;padding:0;font-size:13.5px;color:var(--k-sage);text-decoration:underline">Size guide</button>
          </div>
          <div class="sizes">
            @foreach($sizes as $size)
              <button type="button" class="size {{ (string) $size->id === (string) $defaultSize ? 'on' : '' }}" data-size="{{ $size->id }}">{{ $size->label }}</button>
            @endforeach
          </div>

          <div class="sizeguide" data-sizeguide hidden>
            <p class="t">Size guide</p>
            <table class="sizetable">
              <thead><tr><th>Size</th><th>Height</th><th>Weight</th></tr></thead>
              <tbody>
                @foreach($product->sizeScale->options as $opt)
                  <tr><td>{{ $opt->label }}</td><td>{{ $opt->heightRange() ?? '—' }}</td><td>{{ $opt->weightRange() ?? '—' }}</td></tr>
                @endforeach
              </tbody>
            </table>
            <p class="muted" style="margin:12px 0 0;font-size:13.5px">Between sizes? Size up — babies grow through a band in roughly six weeks.</p>
          </div>

          <p class="stocknote" data-stock-note>{{ $anyStock ? 'In stock · ships from Colombo' : 'Sold out' }}</p>

          <div class="btnrow" style="margin-bottom:12px">
            <button class="btn" data-add type="submit" @disabled(!$anyStock)>{{ $anyStock ? 'Add to cart' : 'Sold out' }}</button>
          </div>
          <p class="muted" style="margin:0 0 24px;font-size:13.5px">Order before 2pm for same-day dispatch · Cash on delivery available</p>
        </form>

        <details class="acc" open>
          <summary>Fabric &amp; care</summary>
          @if($product->description)<p>{{ $product->description }}</p>@endif
          @if($product->fabric_care)
            <ul>@foreach(preg_split('/\r?\n/', trim($product->fabric_care)) as $line)@if(trim($line) !== '')<li>{{ $line }}</li>@endif @endforeach</ul>
          @endif
        </details>
        <details class="acc">
          <summary>Delivery &amp; returns</summary>
          <p>Colombo 1–2 days, rest of the island 2–4 days. Free over {{ money(config('kayaa.free_shipping_over')) }}, otherwise {{ money(config('kayaa.shipping_fee')) }}.</p>
          <p style="margin-bottom:16px">Exchange any unworn item within 14 days with tags on. We cover return delivery on size swaps.</p>
        </details>
      </div>
    </div>

    @if($faqs->isNotEmpty())
      <section class="faqs" id="faqs">
        <h2 class="h2">Questions about this product</h2>
        @foreach($faqs as $faq)
          <details class="acc">
            <summary>{{ $faq->question }}</summary>
            <p>{!! nl2br(e($faq->answer)) !!}</p>
          </details>
        @endforeach
      </section>
    @endif

    <section class="reviews" id="reviews">
      <h2 class="h2">Reviews</h2>
      <div class="split" style="gap:30px;align-items:flex-start">
        <div class="main" style="flex-basis:320px">
          @if($ratingCount > 0)
            <div class="ratingsummary">
              <div class="big">
                <strong>{{ number_format($ratingAvg, 1) }}</strong>
                <x-store.stars :rating="$ratingAvg" size="17" />
                <span class="muted small">{{ $ratingCount }} {{ Str::plural('review', $ratingCount) }}</span>
              </div>
              <div class="bars">
                @for($star = 5; $star >= 1; $star--)
                  @php $n = (int) ($ratingBreakdown[$star] ?? 0); @endphp
                  <div class="bar"><span>{{ $star }}★</span><i><b style="width:{{ $ratingCount ? round($n / $ratingCount * 100) : 0 }}%"></b></i><span class="muted small">{{ $n }}</span></div>
                @endfor
              </div>
            </div>

            <div class="reviewlist">
              @foreach($reviews as $review)
                <article class="review">
                  <div class="head">
                    <x-store.stars :rating="$review->rating" />
                    <span class="muted small">{{ $review->created_at->format('d M Y') }}</span>
                    @if($review->is_verified_purchase)<span class="pill verified">Verified purchase</span>@endif
                  </div>
                  <p class="who">{{ $review->displayName() }}</p>
                  @if($review->title)<p class="t">{{ $review->title }}</p>@endif
                  <p class="body">{{ $review->body }}</p>
                  @if($review->admin_reply)<p class="reply"><strong>Kayaa:</strong> {{ $review->admin_reply }}</p>@endif
                </article>
              @endforeach
            </div>
          @else
            <p class="muted">No reviews yet — be the first to tell other parents how it fits.</p>
          @endif
        </div>

        <div class="side panel" style="flex-basis:300px">
          @if($hasReviewed)
            <h3 class="h3" style="font-size:17px">Thanks for your review</h3>
            <p class="muted small" style="margin:0">You have already reviewed this product. See it under <a href="{{ route('account.reviews') }}">my reviews</a>.</p>
          @else
            <h3 class="h3" style="font-size:17px">Write a review</h3>
            <p class="muted small" style="margin:0 0 14px">Reviews are checked by us before they appear.</p>
            <form method="post" action="{{ route('reviews.store', $product) }}">
              @csrf
              <div @class(['field', 'invalid' => $errors->has('rating')])>
                <label>Your rating</label>
                <div class="ratingpick">
                  @for($i = 5; $i >= 1; $i--)
                    <input type="radio" id="r-{{ $i }}" name="rating" value="{{ $i }}" @checked(old('rating') == $i) required>
                    <label for="r-{{ $i }}" title="{{ $i }} out of 5">★</label>
                  @endfor
                </div>
                @error('rating')<span class="err">{{ $message }}</span>@enderror
              </div>
              @guest
                <x-store.field name="name" label="Your name" required />
                <x-store.field name="email" label="Email (not shown)" type="email" />
              @endguest
              <x-store.field name="title" label="Headline (optional)" placeholder="Soft and true to size" />
              <x-store.field name="body" label="Your review" type="textarea" rows="4" required />
              <button class="btn dark" style="width:100%">Submit review</button>
            </form>
            @guest
              <p class="muted small" style="margin:12px 0 0"><a href="{{ route('account.login') }}">Sign in</a> to have your review marked as a verified purchase.</p>
            @endguest
          @endif
        </div>
      </div>
    </section>

    @if($related->isNotEmpty())
      <section style="margin-top:40px">
        <h2 class="h2">Goes well with</h2>
        <div class="grid">
          @foreach($related as $p)<x-store.product-card :product="$p" />@endforeach
        </div>
      </section>
    @endif
  </div>
</x-layouts.store>
