{{-- Contact, from the prototype's html/contact.html. The contact cards take the
     numbers from Admin > Settings > Contact details; the form is the existing
     one (name, contact, order_reference, message). --}}
@php
  $wa = \App\Support\StoreContact::whatsapp();
  $waHref = \App\Support\StoreContact::whatsappHref();
  $tel = \App\Support\StoreContact::phone();
  $telHref = \App\Support\StoreContact::phoneHref();
@endphp
<x-layouts.store title="Contact" page="contact">
  <div class="container content">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="{{ route('home') }}">Home</a></li>
        <li aria-current="page">Contact</li>
      </ol>
    </nav>
    <header class="content__head">
      <h1>Contact us</h1>
      <p class="content__lede">Fastest on WhatsApp, 9am–6pm Monday to Saturday</p>
    </header>

    <div data-contact-page>
      <ul class="contact-cards" role="list">
        @if($waHref)
          <li class="contact-card">
            <span class="contact-card__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-message-circle"></use></svg></span>
            <h2>WhatsApp</h2>
            <p class="contact-card__value">{{ $wa }}</p>
            <p>Order help, sizing, exchanges</p>
            <a class="link-arrow" href="{{ $waHref }}" target="_blank" rel="noopener">Chat on WhatsApp <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
          </li>
        @endif
        @if($tel)
          <li class="contact-card">
            <span class="contact-card__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-message-circle"></use></svg></span>
            <h2>Call us</h2>
            <p class="contact-card__value">{{ $tel }}</p>
            <a class="link-arrow" href="{{ $telHref }}">Call now <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
          </li>
        @endif
        <li class="contact-card">
          <span class="contact-card__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-mail"></use></svg></span>
          <h2>Email</h2>
          <p class="contact-card__value">{{ config('kayaa.email') }}</p>
          <a class="link-arrow" href="mailto:{{ config('kayaa.email') }}">Send an email <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
        </li>
        <li class="contact-card">
          <span class="contact-card__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-clock"></use></svg></span>
          <h2>Hours</h2>
          <p class="contact-card__value">Mon–Sat 9am–6pm</p>
        </li>
        <li class="contact-card">
          <span class="contact-card__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-truck"></use></svg></span>
          <h2>Delivery</h2>
          <p class="contact-card__value">Island-wide by courier</p>
          <a class="link-arrow" href="{{ route('pages.show', 'delivery') }}">Delivery information <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
        </li>
      </ul>

      <div class="contact-main">
        <section class="contact-form" aria-labelledby="contact-title">
          <h2 id="contact-title">Send us a message</h2>
          <p class="contact-form__lede">We'll reply on the number or email you give us.</p>

          @if($errors->any())
            <div class="alert alert--error" role="alert" tabindex="-1" id="contact-summary">
              <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-circle-alert"></use></svg>
              <div><p class="alert__title">Please check your message</p><ul class="alert__list">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            </div>
          @endif

          <form class="contact-form__fields" method="post" action="{{ route('pages.contact.send') }}" novalidate data-auth-form data-summary="contact-summary">
            @csrf
            <div class="field" data-field>
              <label for="name">Your name</label>
              <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}" data-required-message="Enter your name">
              <p class="field__error" id="name-error" hidden></p>
            </div>
            <div class="field" data-field>
              <label for="contact">Mobile or email</label>
              <input id="contact" name="contact" type="text" required value="{{ old('contact') }}" aria-describedby="contact-hint" data-required-message="Tell us how to reach you">
              <p class="field__hint" id="contact-hint">However you'd like us to reply.</p>
              <p class="field__error" id="contact-error" hidden></p>
            </div>
            <div class="field" data-field>
              <label for="order_reference">Order number (optional)</label>
              <input id="order_reference" name="order_reference" type="text" placeholder="KY-260917-AB12" maxlength="20"
                     autocapitalize="characters" autocomplete="off" spellcheck="false" value="{{ old('order_reference') }}">
              <p class="field__error" id="order_reference-error" hidden></p>
            </div>
            <div class="field" data-field>
              <label for="message">Message</label>
              <textarea id="message" name="message" rows="5" required data-required-message="Write your message">{{ old('message') }}</textarea>
              <p class="field__error" id="message-error" hidden></p>
            </div>
            <button type="submit" class="btn btn--primary btn--lg">Send message</button>
          </form>
        </section>

        <section class="contact-faqs" aria-labelledby="faq-title">
          <h2 id="faq-title">Common questions</h2>
          <div class="accordion">
            @forelse($faqs as $faq)
              <details class="accordion__item contact-faq">
                <summary class="accordion__summary">{{ $faq->question }} <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
                <div class="accordion__body">{!! nl2br(e($faq->answer)) !!}</div>
              </details>
            @empty
              {{-- The prototype's four default questions, shown until Admin > FAQs has
                   contact questions of its own. --}}
              <details class="accordion__item contact-faq">
                <summary class="accordion__summary">How quickly will you reply? <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
                <div class="accordion__body"><p>WhatsApp is fastest, Mon–Sat 9am–6pm. We reply to messages within one working day.</p></div>
              </details>
              <details class="accordion__item contact-faq">
                <summary class="accordion__summary">Can I change my delivery address after ordering? <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
                <div class="accordion__body"><p>Contact us as soon as possible with your order number and mobile number. We can change the address only if the order has not been dispatched.</p></div>
              </details>
              <details class="accordion__item contact-faq">
                <summary class="accordion__summary">How do I track my order? <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
                <div class="accordion__body"><p>Open <a href="{{ route('orders.track') }}">Track order</a> and enter your order number and the mobile number you used at checkout.</p></div>
              </details>
              <details class="accordion__item contact-faq">
                <summary class="accordion__summary">I paid but my order shows "pending". What should I do? <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
                <div class="accordion__body"><p>Card payments can take a few minutes to be confirmed. Please do not pay again. Check your order on the <a href="{{ route('orders.track') }}">Track order</a> page, and if it has not updated after half an hour, contact us with your order number.</p></div>
              </details>
            @endforelse
          </div>
        </section>
      </div>
    </div>
  </div>
</x-layouts.store>
