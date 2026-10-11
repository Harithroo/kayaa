{{-- Privacy policy, with the full text from the prototype's html/privacy.html
     (docs/content/privacy-policy.md), which is written against Sri Lanka's
     Personal Data Protection Act, No. 9 of 2022.

     TODO before launch: the registered business name and registered address are
     facts only Kayaa knows, and the prototype left them as markers. They are
     marked below. Have a Sri Lankan lawyer review this page. --}}
<x-store.content-page title="Privacy" heading="Privacy policy" page="privacy"
  metaDescription="What personal information Kayaa collects when you shop with us, why we need it, who we share it with and what choices you have."
  :lede="'This policy explains what personal information Kayaa collects when you shop with us, why we need it, who we share it with and what choices you have. We have kept it as plain as we can. If anything is unclear, write to us at '.config('kayaa.email').' and we will explain.'"
  :toc="[
    '#who-we-are' => 'Who we are',
    '#collect' => 'Information we collect',
    '#use' => 'How we use your information',
    '#share' => 'Who we share it with',
    '#cookies' => 'Cookies and similar technologies',
    '#retention' => 'How long we keep it',
    '#safety' => 'Keeping your information safe',
    '#where' => 'Where your information is processed',
    '#choices' => 'Your rights',
    '#children' => 'Children',
    '#changes' => 'Changes to this policy',
    '#contact' => 'Contact us',
  ]">

  <h2 id="who-we-are">Who we are <x-store.anchor id="who-we-are" label="Who we are" /></h2>
  <p>Kayaa is an online baby clothing store serving customers across Sri Lanka. In this policy "we", "us" and "Kayaa" mean Kayaa, Colombo, Sri Lanka. We decide how and why your personal information is used, which makes us the "controller" of it. You can reach us at {{ config('kayaa.email') }}.</p>

  <h2 id="collect">Information we collect <x-store.anchor id="collect" label="Information we collect" /></h2>
  <p><strong>When you place an order.</strong> Your name, mobile number, delivery address, district and any delivery notes, what you ordered, and your email address if you choose to give one. Your mobile number is how the courier reaches you and how you can look up your order.</p>
  <p><strong>When you create an account.</strong> Your name, email address, mobile number and a password. We store your password in a scrambled (hashed) form, so nobody at Kayaa can read it.</p>
  <p><strong>When you write a review.</strong> Your name, an email address (never shown publicly), your rating, an optional headline and your comments. If you are signed in, we also record whether you bought the product from us, so we can show a "Verified purchase" label.</p>
  <p><strong>When you contact us.</strong> Your name, your mobile number or email, an optional order number and your message. If you message us on WhatsApp, WhatsApp's own privacy policy also applies to that conversation.</p>
  <p><strong>When you save items.</strong> Items you add to your wishlist are stored with your account, or in your browser session if you are not signed in.</p>
  <p><strong>When you pay.</strong> You enter your card details on the secure payment page of our payment provider, Onepay. We do not see or store your full card number. We receive the payment status and a reference so we know whether your order is paid.</p>
  <p><strong>Automatically.</strong> Like most websites we receive technical information such as your IP address, browser, device and the pages you visit, and we use the cookies described below.</p>
  <p>We do not ask for your baby's name, date of birth or photo. The clothing size you pick is used only to process your order.</p>

  <h2 id="use">How we use your information <x-store.anchor id="use" label="How we use your information" /></h2>
  <ul>
    <li>to process and deliver your order and keep you informed about it</li>
    <li>to let you look up your order with your order number and mobile number</li>
    <li>to send you updates about your order</li>
    <li>to run your account, including resetting your password</li>
    <li>to answer questions and handle returns and refunds</li>
    <li>to publish reviews after we have checked them</li>
    <li>to keep the site secure and prevent fraud and abuse, for example by limiting repeated attempts</li>
    <li>to keep the records we must keep for accounting and legal reasons</li>
  </ul>
  <p>We do not send marketing messages today. If that changes, we will ask for your consent first and you will be able to opt out at any time.</p>
  <p><strong>Why we may use it.</strong> We use your information because it is needed to fulfil your order, because the law requires us to keep certain records, because we have a genuine interest in running a secure shop, or because you have agreed (for example when you choose "Keep me signed in").</p>

  <h2 id="share">Who we share it with <x-store.anchor id="share" label="Who we share it with" /></h2>
  <p>We share only what is needed, and we do not sell your personal information.</p>
  <ul>
    <li><strong>Delivery partners:</strong> your name, mobile number, delivery address and parcel details, so they can deliver your order.</li>
    <li><strong>Onepay:</strong> to take your card payment.</li>
    <li><strong>Service providers</strong> that help us run the site, such as hosting and the service that sends account emails.</li>
    <li><strong>Professional advisers and authorities</strong> such as accountants, lawyers and public bodies, where the law requires it.</li>
  </ul>

  <h2 id="cookies">Cookies and similar technologies <x-store.anchor id="cookies" label="Cookies and similar technologies" /></h2>
  <p>We use only the cookies the site needs to work:</p>
  <ul>
    <li>a session cookie that keeps your bag and keeps you signed in while you browse</li>
    <li>a security token that protects forms from misuse</li>
    <li>a "keep me signed in" cookie, only if you tick that box</li>
  </ul>
  <p>You can block or delete cookies in your browser settings. If you block the essential ones, your bag, checkout and account will not work.</p>

  <h2 id="retention">How long we keep it <x-store.anchor id="retention" label="How long we keep it" /></h2>
  <div class="table-wrap">
    <table class="size-table size-table--two">
      <caption class="visually-hidden">How long we keep it</caption>
      <thead><tr><th scope="col">Information</th><th scope="col">How long</th></tr></thead>
      <tbody>
        <tr><th scope="row">Order records</th><td data-label="How long"><span>As long as accounting and tax rules require</span></td></tr>
        <tr><th scope="row">Account details</th><td data-label="How long"><span>Until you ask us to close your account</span></td></tr>
        <tr><th scope="row">Reviews</th><td data-label="How long"><span>While the product is on sale, or until you ask us to remove yours</span></td></tr>
        <tr><th scope="row">Contact messages</th><td data-label="How long"><span>12 months</span></td></tr>
        <tr><th scope="row">Security logs</th><td data-label="How long"><span>90 days</span></td></tr>
      </tbody>
    </table>
  </div>

  <h2 id="safety">Keeping your information safe <x-store.anchor id="safety" label="Keeping your information safe" /></h2>
  <p>Our website uses an encrypted connection (HTTPS), passwords are stored hashed, access to personal information is limited to people who need it, and repeated sign-in or form attempts are limited. No system is completely secure. If a personal data breach affects you, we will tell you and the authorities as the law requires.</p>

  <h2 id="where">Where your information is processed <x-store.anchor id="where" label="Where your information is processed" /></h2>
  <p>Some of our service providers may store or process information outside Sri Lanka. Where that happens we choose providers that protect personal information to a standard consistent with Sri Lankan law.</p>

  <h2 id="choices">Your rights <x-store.anchor id="choices" label="Your rights" /></h2>
  <p>Under the Personal Data Protection Act, No. 9 of 2022 you can ask us to:</p>
  <ul>
    <li>give you a copy of the personal information we hold about you</li>
    <li>correct information that is wrong</li>
    <li>erase information we no longer need, subject to records we are required to keep</li>
    <li>restrict or stop some uses of your information</li>
    <li>stop using your information for direct marketing</li>
    <li>accept your withdrawal of consent where we rely on consent</li>
  </ul>
  <p>To use any of these rights, email {{ config('kayaa.email') }} with your name, mobile number and order number so we can check it is you. We will reply within the time the law requires. If you are not happy with our answer you can complain to the Data Protection Authority of Sri Lanka.</p>
  <p><strong>Closing your account.</strong> We must keep order records for accounting, so when you ask us to close your account we remove or anonymise your personal details rather than deleting the order record itself.</p>

  <h2 id="children">Children <x-store.anchor id="children" label="Children" /></h2>
  <p>Our website is for parents, guardians and gift buyers. It is not intended for children and we do not knowingly collect information from them.</p>

  <h2 id="changes">Changes to this policy <x-store.anchor id="changes" label="Changes to this policy" /></h2>
  <p>If we change this policy we will update the date at the top of this page. For significant changes we will also display a notice on the site.</p>

  <h2 id="contact">Contact us <x-store.anchor id="contact" label="Contact us" /></h2>
  <p>
    Kayaa, Colombo, Sri Lanka<br>
    Email: <a href="mailto:{{ config('kayaa.email') }}">{{ config('kayaa.email') }}</a>
    @if(\App\Support\StoreContact::whatsapp())<br>WhatsApp: {{ \App\Support\StoreContact::whatsapp() }}@endif
  </p>

  <x-slot:after>
    <x-store.help-card />
  </x-slot:after>
</x-store.content-page>
