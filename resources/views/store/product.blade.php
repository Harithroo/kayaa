@php
  $colors = $product->colors();
  $sizes = $product->sizeOptions();
  $firstColorId = $colors->first()?->id ?? 0;
  // Default size: first one in stock for the default colour
  $defaultSize = collect($matrix[$firstColorId] ?? [])->filter(fn ($v) => $v['stock'] > 0)->keys()->first();
  $images = $product->images;
  $main = $images->first();
  $tints = ['t1','t2','t3','t4','t5','t6'];
  $anyStock = $product->totalStock() > 0;
@endphp
<x-layouts.store :title="$product->name" :metaDescription="Str::limit($product->description, 150)" :category="$product->category">
  <div class="wrap section pdp">
    <p class="crumbs"><a href="{{ route('home') }}">Home</a> › <a href="{{ $product->category->url() }}">{{ $product->category->name }}</a> › {{ $product->name }}</p>
    <div class="pdp-grid">
      <div class="gallery" data-gallery>
        @if($main)
          <div class="ph"><img data-gallery-main src="{{ $main->url() }}" alt="{{ $main->alt ?? $product->name }}"></div>
        @else
          <x-store.photo :tint="$tints[$product->id % 6]" />
        @endif
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
          @if($product->collection_label)<p class="eyebrow" style="margin-top:18px">{{ $product->collection_label }}</p>@endif
          <h1>{{ $product->name }}</h1>
          <p style="margin:0 0 18px"><span class="price" style="font-size:21px" data-price>{{ money($product->minPrice()) }}</span><span class="was" data-was hidden></span></p>

          @if($colors->isNotEmpty())
            <p style="margin:0 0 8px;font-size:14px;font-weight:600">Colour — <span data-color-name>{{ $colors->first()->name }}</span></p>
            <div class="swatches" style="margin-bottom:20px">
              @foreach($colors as $color)
                <button type="button" class="sw {{ $loop->first ? 'on' : '' }}" style="background:{{ $color->hex }}" aria-label="{{ $color->name }}" data-color="{{ $color->id }}"></button>
              @endforeach
            </div>
          @endif

          <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
            <p style="margin:0;font-size:14px;font-weight:600">Size</p>
            <a href="{{ route('pages.size-guide') }}" style="font-size:13.5px">Size guide</a>
          </div>
          <div class="sizes" style="margin-bottom:12px">
            @foreach($sizes as $size)
              <button type="button" class="size {{ (string) $size->id === (string) $defaultSize ? 'on' : '' }}" data-size="{{ $size->id }}">{{ $size->label }}</button>
            @endforeach
          </div>

          <p class="stocknote" data-stock-note></p>

          <button class="btn" style="margin-bottom:10px" data-add type="submit" @disabled(!$anyStock)>{{ $anyStock ? 'Add to cart' : 'Sold out' }}</button>
          <p style="margin:0 0 26px;font-size:13.5px;color:var(--ink-soft)">Order before 2pm for same-day dispatch · Cash on delivery available</p>
        </form>

        <details class="acc" open>
          <summary>Fabric &amp; care</summary>
          @if($product->description)<p>{{ $product->description }}</p>@endif
          @if($product->fabric_care)
            <ul>@foreach(preg_split('/\r?\n/', trim($product->fabric_care)) as $line)@if(trim($line) !== '')<li>{{ $line }}</li>@endif @endforeach</ul>
          @endif
        </details>
        <details class="acc">
          <summary>Size &amp; fit</summary>
          <table class="sizetable">
            <thead><tr><th>Size</th><th>Height</th><th>Weight</th></tr></thead>
            <tbody>
              @foreach($product->sizeScale->options as $opt)
                <tr><td>{{ $opt->label }}</td><td>{{ $opt->heightRange() ?? '—' }}</td><td>{{ $opt->weightRange() ?? '—' }}</td></tr>
              @endforeach
            </tbody>
          </table>
          <p>Between sizes? Size up — babies grow through a band in roughly six weeks.</p>
        </details>
        <details class="acc">
          <summary>Delivery &amp; returns</summary>
          <p>Colombo 1–2 days, rest of the island 2–4 days. Free over {{ money(config('kayaa.free_shipping_over')) }}, otherwise {{ money(config('kayaa.shipping_fee')) }}.</p>
          <p>Exchange any unworn item within 14 days with tags on. We cover return delivery on size swaps.</p>
        </details>
      </div>
    </div>

    @if($related->isNotEmpty())
      <section style="margin-top:44px">
        <div class="sechead"><h2 class="sec">Goes well with</h2></div>
        <div class="grid">
          @foreach($related as $p)<x-store.product-card :product="$p" />@endforeach
        </div>
      </section>
    @endif
  </div>
</x-layouts.store>
