{{-- tools/partials/quick-add.html. app.js fills the size and colour lists from the
     card's data-sizes / data-colours and the labels in window.KAYAA_CATALOGUE; the
     form posts the chosen pair to the server, which resolves it to a variant. --}}
<div class="sheet" data-layer="quick-add" data-quick-add-sheet role="dialog" aria-modal="true" aria-labelledby="qa-title" aria-hidden="true">
  <div class="sheet__backdrop" data-close-layer></div>
  <div class="sheet__panel">
    <div class="quick-add__head">
      <div class="media media--tile quick-add__thumb" data-placeholder data-qa-thumb style="--tone: var(--tone-1)"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-image"></use></svg></div>
      <div class="quick-add__title">
        <h2 id="qa-title" data-qa-name>Product name</h2>
        <p class="price"><span class="price__now" data-qa-price>Rs. 0</span> <s class="price__was" data-qa-was hidden></s></p>
      </div>
      <button type="button" class="icon-btn icon-btn--plain quick-add__close" data-close-layer aria-label="Close quick add">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-x"></use></svg>
      </button>
    </div>
    <form data-quick-add-form action="{{ route('cart.quick-add') }}" method="post">
      @csrf
      <input type="hidden" name="product" data-qa-product value="">
      <fieldset class="quick-add__group">
        <legend>Size</legend>
        <div class="quick-add__options" data-qa-sizes></div>
      </fieldset>
      <fieldset class="quick-add__group">
        <legend>Colour</legend>
        <div class="quick-add__options quick-add__options--colours" data-qa-colours></div>
      </fieldset>
      <button type="submit" class="btn btn--primary btn--lg btn--block">Add to cart</button>
    </form>
  </div>
</div>
