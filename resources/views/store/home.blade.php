<x-layouts.store>
  <section class="hero">
    <div class="wrap hero-in">
      <div class="hero-copy">
        <p class="eyebrow">Newborn – 2 years</p>
        <h1>Soft cottons for the first two years.</h1>
        <p>Breathable cotton and bamboo, cut for Sri Lankan weather. Sized by age, so you order once and it fits.</p>
        <div class="btnrow">
          <a class="btn" href="{{ route('shop.index') }}">Shop by age</a>
          <a class="btn ghost" href="{{ route('pages.show', 'about') }}">Our story</a>
        </div>
      </div>
      <div class="hero-art">
        <x-store.photo tint="butter" cap="hero shot" />
        <x-store.photo tint="sage" cap="detail shot" />
      </div>
    </div>
  </section>

  @if($sizes->isNotEmpty())
  <section class="section">
    <div class="wrap">
      <p class="eyebrow">Find the right fit</p>
      <h2 class="h2">Shop by age</h2>
      <div class="chips">
        @foreach($sizes->take(7) as $size)
          <a class="chip" href="{{ route('shop.index', ['size' => $size->label]) }}">{{ $size->label }}</a>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  @if($tiles->isNotEmpty())
  <section class="section">
    <div class="wrap">
      <h2 class="h2">Browse categories</h2>
      <div class="tiles">
        @php $tints = ['butter','sage','blush','sand','cloud','butter']; @endphp
        @foreach($tiles as $cat)
          <a class="tile" href="{{ $cat->url() }}"><div class="t-{{ $tints[$loop->index % 6] }}"><span>{{ $cat->name }}</span></div></a>
        @endforeach
        <a class="tile" href="{{ route('shop.index', ['sale' => 1]) }}"><div class="t-butter"><span>Sale</span></div></a>
      </div>
    </div>
  </section>
  @endif

  <section class="section">
    <div class="wrap">
      <div class="sechead">
        <h2 class="h2">New this week</h2>
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

  @foreach($promos as $banner)
    <section class="section"><div class="wrap"><x-store.banner :banner="$banner" /></div></section>
  @endforeach

  @if($bestsellers->isNotEmpty())
  <section class="section">
    <div class="wrap">
      <h2 class="h2">Bestsellers</h2>
      <div class="grid">
        @foreach($bestsellers as $product)<x-store.product-card :product="$product" />@endforeach
      </div>
    </div>
  </section>
  @endif

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
