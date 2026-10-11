{{-- Returns & exchanges, with the full copy from the prototype's html/returns.html
     (docs/content/returns.md). Day counts come from the store config. --}}
<x-store.content-page title="Returns &amp; exchanges" heading="Returns &amp; exchanges" crumb="Returns" page="returns"
  metaDescription="Kayaa's returns and exchanges policy: free size swaps within 14 days, refunds, faulty items and what we cannot take back."
  lede="Babies grow quickly and sizes can be hard to judge. If something does not fit or is not right, we want to make it easy to put it right."
  :toc="[
    '#short' => 'The short version',
    '#exchanges' => 'Wrong size? Swap it free',
    '#refunds' => 'Refunds',
    '#damaged' => 'Faulty or wrong items',
    '#not-returnable' => 'What we cannot take back',
    '#cancelling' => 'Cancelling before dispatch',
    '#rights' => 'Your legal rights',
    '#help' => 'Need help?',
  ]">

  <h2 id="short">The short version <x-store.anchor id="short" label="The short version" /></h2>
  <div class="notice">
    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg>
    <div>
      <ul>
        <li>Exchange any unworn, unwashed item with its tags on within 14 days of delivery.</li>
        <li>Wrong size? We cover the return courier cost on size swaps.</li>
        <li>Prefer a refund? We refund the item price within 7 days of receiving it back.</li>
        <li>Faulty or wrong item? Send us a photo within 7 days.</li>
      </ul>
    </div>
  </div>

  <h2 id="exchanges">Wrong size? Swap it free <x-store.anchor id="exchanges" label="Wrong size? Swap it free" /></h2>
  <p>Message us on WhatsApp with your order number and the size you would like. We arrange the pick-up and cover the courier cost for size swaps. The new size is sent once we have received the original, subject to stock. If the size you need is sold out, we refund the item instead. Not sure which size to choose? See our <a href="{{ route('pages.size-guide') }}">size guide</a>.</p>

  <h2 id="refunds">Refunds <x-store.anchor id="refunds" label="Refunds" /></h2>
  <p>If you would rather have a refund than a swap, we refund the price of the item within 7 days of receiving it back. The refund goes back to the card you paid with. Delivery fees are not refunded unless the item was faulty or we sent the wrong thing.</p>

  <h2 id="damaged">Faulty or wrong items <x-store.anchor id="damaged" label="Faulty or wrong items" /></h2>
  <p>If an item arrives faulty, damaged or not what you ordered, send us a photo within 7 days of delivery with your order number. We will replace it or refund it, including delivery.</p>

  <h2 id="not-returnable">What we cannot take back <x-store.anchor id="not-returnable" label="What we cannot take back" /></h2>
  <p>For hygiene, we cannot accept returns on items that have been worn or washed, or on sale items marked final.</p>

  <h2 id="cancelling">Cancelling before dispatch <x-store.anchor id="cancelling" label="Cancelling before dispatch" /></h2>
  <p>If your order has not been dispatched you may be able to cancel it instead. With an account you can cancel from your order page while it shows "pending". Otherwise contact us as soon as possible.</p>

  <h2 id="rights">Your legal rights <x-store.anchor id="rights" label="Your legal rights" /></h2>
  <p>This policy does not affect your rights under Sri Lankan consumer law.</p>

  <h2 id="help">Need help? <x-store.anchor id="help" label="Need help?" /></h2>
  <p>WhatsApp us (Mon–Sat 9am–6pm) or use the <a href="{{ route('pages.contact') }}">contact form</a>. Please have your order number ready.</p>

  <x-slot:after>
    <x-store.help-card />
  </x-slot:after>
</x-store.content-page>
