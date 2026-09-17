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
