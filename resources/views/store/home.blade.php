<x-layouts.store>
  <section class="hero">
    <div class="wrap hero-grid">
      <div>
        <p class="eyebrow">Newborn – 2 years</p>
        <h1>Soft cottons for the first two years.</h1>
        <p>Breathable cotton and bamboo, cut for Sri Lankan weather. Sized by age, so you order once and it fits.</p>
        <a class="btn" href="{{ route('shop.index') }}">Shop by age</a>
      </div>
      <div class="hero-art">
        <x-store.photo tint="t2" />
        <x-store.photo tint="t1" />
      </div>
    </div>
  </section>

  @if($sizes->isNotEmpty())
  <section class="section">
    <div class="wrap">
      <p class="eyebrow">Find the right fit</p>
      <div class="sechead"><h2 class="sec">Shop by age</h2></div>
      <div class="ages">
        @foreach($sizes->take(7) as $size)
          <a class="age" href="{{ route('shop.index', ['size' => $size->label]) }}">{{ $size->label }}</a>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  <section class="section" style="padding-top:0">
    <div class="wrap">
      <div class="sechead">
        <h2 class="sec">New this week</h2>
        <a class="seclink" href="{{ route('shop.index') }}">See all</a>
      </div>
      @if($newIn->isEmpty())
        <p class="empty">Products are on their way. Check back soon.</p>
      @else
        <div class="grid">
          @foreach($newIn as $product)<x-store.product-card :product="$product" />@endforeach
        </div>
      @endif
    </div>
  </section>

  <section class="trust">
    <div class="wrap">
      <ul>
        <li><x-store.tick /><div><strong>Cash on delivery</strong><span>Pay the courier when it arrives. Available island-wide.</span></div></li>
        <li><x-store.tick /><div><strong>Swap sizes free</strong><span>Wrong size? Exchange within 14 days, unworn.</span></div></li>
        <li><x-store.tick /><div><strong>Tested for sensitive skin</strong><span>OEKO-TEX certified cotton, no harsh dyes.</span></div></li>
      </ul>
    </div>
  </section>
</x-layouts.store>
