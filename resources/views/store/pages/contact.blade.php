@php $wa = config('kayaa.whatsapp'); $waHref = $wa ? 'https://wa.me/94'.preg_replace('/\D+/', '', ltrim($wa, '0')) : null; @endphp
<x-layouts.store title="Contact us">
  <div class="section">
  <div class="wrap">
    <h1 class="h2 page">Contact us</h1>
    <p class="muted" style="margin:0 0 22px;font-size:15px">Fastest on WhatsApp, 9am–6pm Monday to Saturday.</p>
    <div class="split" style="gap:30px">
      <div class="main" style="flex-basis:300px">
        @if($waHref)
          <a class="wa-card" href="{{ $waHref }}">
            <span class="dot"></span>
            <span><strong>WhatsApp {{ $wa }}</strong><span>Order help, sizing, exchanges</span></span>
          </a>
        @endif
        <div class="rows">
          <div><p class="k">Email</p><p class="v"><a href="mailto:{{ config('kayaa.email') }}">{{ config('kayaa.email') }}</a></p></div>
          <div><p class="k">Hours</p><p class="v">Mon–Sat 9am–6pm</p></div>
          <div><p class="k">Delivery</p><p class="v">Island-wide by courier · cash on delivery available</p></div>
        </div>
      </div>
      <form class="side panel" method="post" action="{{ route('pages.contact.send') }}" style="flex-basis:300px">
        @csrf
        <h2 class="h3" style="font-size:17px">Send a message</h2>
        <x-store.field name="name" label="Your name" required />
        <x-store.field name="contact" label="Mobile or email" required />
        <x-store.field name="order_reference" label="Order number (optional)" placeholder="KY-260917-AB12" />
        <x-store.field name="message" label="Message" type="textarea" rows="4" required />
        <button class="btn dark" style="width:100%">Send message</button>
      </form>
    </div>
  </div>
  </div>
</x-layouts.store>
