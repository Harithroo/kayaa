{{-- "Still need help?" card at the foot of the written pages (prototype:
     html/delivery.html and friends). --}}
@php
  $wa = \App\Support\StoreContact::whatsapp();
  $waHref = \App\Support\StoreContact::whatsappHref();
@endphp
<section class="help-card" aria-labelledby="help-title">
  <div>
    <h2 id="help-title">Still need help?</h2>
    <p>
      @if($wa)
        Message us on WhatsApp {{ $wa }} or send us a note from the contact page.
      @else
        Send us a note from the contact page.
      @endif
    </p>
  </div>
  <div class="help-card__actions">
    @if($waHref)
      <a class="btn btn--primary" href="{{ $waHref }}" target="_blank" rel="noopener">
        <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-message-circle"></use></svg> Chat on WhatsApp
      </a>
    @endif
    <a class="btn btn--secondary" href="{{ route('pages.contact') }}">Contact us</a>
  </div>
</section>
