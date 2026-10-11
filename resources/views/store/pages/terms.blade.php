{{-- Terms & conditions, with the full text from the prototype's html/terms.html
     (docs/content/terms.md). Fees come from the store config.

     TODO before launch: the registered business name and registered address are
     facts only Kayaa knows. Have a Sri Lankan lawyer review this page. --}}
<x-store.content-page title="Terms" heading="Terms &amp; conditions" crumb="Terms" page="terms"
  metaDescription="The terms your Kayaa order is sold under: orders, prices, payment, delivery, returns, reviews and your rights."
  lede="These terms apply when you buy from Kayaa or use this website."
  :toc="[
    '#about-us' => '1. About us',
    '#using' => '2. Using the website',
    '#accounts' => '3. Your account',
    '#products' => '4. Products and descriptions',
    '#prices' => '5. Prices and delivery charges',
    '#orders' => '6. Placing an order',
    '#payment' => '7. Payment',
    '#delivery' => '8. Delivery',
    '#returns' => '9. Returns and refunds',
    '#cancelling' => '10. Cancelling an order',
    '#reviews' => '11. Reviews',
    '#our-content' => '12. Our content',
    '#responsibility' => '13. Our responsibility',
    '#your-information' => '14. Your information',
    '#changes' => '15. Changes to these terms',
    '#governing-law' => '16. Governing law',
    '#contact' => '17. Contact',
  ]">

  <h2 id="about-us">1. About us <x-store.anchor id="about-us" label="About us" /></h2>
  <p>Kayaa is an online baby clothing store based in Colombo, Sri Lanka ("we", "us"). You can contact us at {{ config('kayaa.email') }}@if(\App\Support\StoreContact::whatsapp()) or on WhatsApp at {{ \App\Support\StoreContact::whatsapp() }}@endif.</p>

  <h2 id="using">2. Using the website <x-store.anchor id="using" label="Using the website" /></h2>
  <p>Use the site lawfully and fairly. Please do not copy it by automated means, try to break or overload it, or use it to send us anything unlawful. To protect the site we limit how often some actions can be repeated, such as signing in or sending forms.</p>

  <h2 id="accounts">3. Your account <x-store.anchor id="accounts" label="Your account" /></h2>
  <p>An account is optional. You can order as a guest and look up an order with your order number and mobile number. If you create an account you must give accurate details and keep your password safe. You are responsible for activity under your account. We may suspend an account that is misused. To close your account, contact us.</p>

  <h2 id="products">4. Products and descriptions <x-store.anchor id="products" label="Products and descriptions" /></h2>
  <p>We describe and photograph our products as accurately as we can. Colours can look slightly different on different screens. Our size guide recommends sizes by age band, with height and weight as the better guide; it is guidance, because babies vary. If you find a mistake in a description, tell us.</p>

  <h2 id="prices">5. Prices and delivery charges <x-store.anchor id="prices" label="Prices and delivery charges" /></h2>
  <p>Prices are in Sri Lankan rupees (Rs). All prices include any applicable taxes. Delivery costs {{ money(config('kayaa.shipping_fee')) }} per order and is free when your order is {{ money(config('kayaa.free_shipping_over')) }} or more. The total you see at checkout is the amount you pay. If we discover an obvious pricing error we may cancel the order and refund any payment.</p>

  <h2 id="orders">6. Placing an order <x-store.anchor id="orders" label="Placing an order" /></h2>
  <p>When you place an order you are making an offer to buy. Each order gets an order number. Your order starts as "pending" and we confirm it once we have checked it. We may decline or cancel an order if an item is unavailable, if there is a pricing error or if we suspect misuse. If we cancel after you have paid, we refund you in full.</p>

  <h2 id="payment">7. Payment <x-store.anchor id="payment" label="Payment" /></h2>
  <p>The payment methods available are shown at checkout. Card payments (Visa and Mastercard) are processed by our payment provider, Onepay; you enter your card details on their secure page and we never see or store your full card number. If a payment fails or stays pending, please do not pay again: check your order on our <a href="{{ route('orders.track') }}">Track order</a> page, and if you were charged but the order shows as failed, contact us with your order number.</p>

  <h2 id="delivery">8. Delivery <x-store.anchor id="delivery" label="Delivery" /></h2>
  <p>We deliver across Sri Lanka. Delivery times are estimates, not guarantees, and depend on your district. Please read our <a href="{{ route('pages.show', 'delivery') }}">delivery information</a>. Ownership and risk pass to you when the parcel is delivered. Please check your parcel on arrival and tell us about any damage or fault within 7 days.</p>

  <h2 id="returns">9. Returns and refunds <x-store.anchor id="returns" label="Returns and refunds" /></h2>
  <p>You can return eligible items under our <a href="{{ route('pages.show', 'returns') }}">returns policy</a>. Refunds go back to the card you paid with.</p>

  <h2 id="cancelling">10. Cancelling an order <x-store.anchor id="cancelling" label="Cancelling an order" /></h2>
  <p>If you have an account, you can cancel an order from your order page while its status is "pending". After that, contact us and we will do our best to help before the order is dispatched. If you have already paid, we refund the amount to your card.</p>

  <h2 id="reviews">11. Reviews <x-store.anchor id="reviews" label="Reviews" /></h2>
  <p>Reviews should be honest and based on your own experience. We check reviews before they appear and may decline or remove any that are unlawful, abusive, irrelevant or not genuine. By submitting a review you allow us to display it on the site. Each account can review a product once. A "Verified purchase" label means the review comes from a signed-in customer whose order for that product was confirmed, shipped or delivered.</p>

  <h2 id="our-content">12. Our content <x-store.anchor id="our-content" label="Our content" /></h2>
  <p>The text, designs, images and logos on the site belong to Kayaa or its licensors. You may view and print pages for your own personal use. Please do not copy or reuse them for other purposes without our written permission.</p>

  <h2 id="responsibility">13. Our responsibility <x-store.anchor id="responsibility" label="Our responsibility" /></h2>
  <p>We are responsible for loss or damage you suffer that is a foreseeable result of our breaking these terms or our own negligence. We are not responsible for delays or failures caused by events outside our reasonable control, such as severe weather, strikes or courier disruption. Nothing in these terms limits any liability that cannot be limited by law, or your rights as a consumer under Sri Lankan law.</p>

  <h2 id="your-information">14. Your information <x-store.anchor id="your-information" label="Your information" /></h2>
  <p>How we use personal information is explained in our <a href="{{ route('pages.show', 'privacy') }}">privacy policy</a>.</p>

  <h2 id="changes">15. Changes to these terms <x-store.anchor id="changes" label="Changes to these terms" /></h2>
  <p>We may update these terms from time to time. The version that applies to your order is the one on this page when you placed it.</p>

  <h2 id="governing-law">16. Governing law <x-store.anchor id="governing-law" label="Governing law" /></h2>
  <p>These terms are governed by the laws of Sri Lanka and the courts of Sri Lanka have jurisdiction.</p>

  <h2 id="contact">17. Contact <x-store.anchor id="contact" label="Contact" /></h2>
  <p>
    Kayaa, Colombo, Sri Lanka<br>
    Email: <a href="mailto:{{ config('kayaa.email') }}">{{ config('kayaa.email') }}</a>@if(\App\Support\StoreContact::whatsapp()) · WhatsApp: {{ \App\Support\StoreContact::whatsapp() }}@endif
  </p>

  <x-slot:after>
    <x-store.help-card />
  </x-slot:after>
</x-store.content-page>
