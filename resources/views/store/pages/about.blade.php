{{-- Our story, with the copy from the prototype's html/about.html
     (docs/content/about.md). The size chips come from the real size scale.

     TODO before launch: the founder's story is Kayaa's to write, and the fabric
     claims (including any OEKO-TEX certification) need confirming before they
     are stated as fact, so neither is asserted here. --}}
<x-store.content-page title="Our story" heading="Our story" crumb="Our story" page="about"
  metaDescription="Kayaa is a Sri Lankan baby clothing brand: soft, honest clothing made for Sri Lankan weather, from newborn up to three years."
  lede="Kayaa is a Sri Lankan baby clothing brand. Soft, honest clothing made for Sri Lankan weather, for babies from newborn up to three years.">
  {{-- No table of contents: the prototype's about page runs full width. --}}

  <h2 id="values">What we care about <x-store.anchor id="values" label="What we care about" /></h2>
  <p><strong>Made for our weather.</strong> Breathable cotton and bamboo pieces that stay comfortable in the heat.</p>
  <p><strong>Sized by age, guided by height and weight.</strong> Our sizes follow age bands, and our <a href="{{ route('pages.size-guide') }}">size guide</a> helps you check them against your baby's height and weight, so you can order once and it fits.</p>
  <p><strong>Simple to shop.</strong> Clear prices, secure card payment, island-wide delivery and an order you can track with your order number and mobile number. If the size is wrong, <a href="{{ route('pages.show', 'returns') }}">swapping it is free</a>.</p>

  @if($navSizes->isNotEmpty())
    <h2 id="sizes">Shop by size <x-store.anchor id="sizes" label="Shop by size" /></h2>
    <p class="chip-list__label">Find pieces for each stage:</p>
    <ul class="chip-list" role="list">
      @foreach($navSizes as $size)
        <li><a class="chip" href="{{ route('shop.index', ['size' => $size->label]) }}">{{ $size->label }}</a></li>
      @endforeach
    </ul>
  @endif

  <h2 id="say-hello">Say hello <x-store.anchor id="say-hello" label="Say hello" /></h2>
  <p>Questions, ideas or feedback? WhatsApp us (Mon–Sat 9am–6pm) or visit the <a href="{{ route('pages.contact') }}">contact page</a>.</p>

  <x-slot:after>
    <x-store.help-card />
  </x-slot:after>
</x-store.content-page>
