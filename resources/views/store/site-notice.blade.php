{{-- The coming-soon and under-maintenance placeholders. One template, filled from
     Admin > Storefront > Site availability (App\Support\SiteMode::notice()).
     Runs on the notice shell — wordmark and a copyright line — because every
     other page is behind the notice too. --}}
@php
  $email = \App\Support\StoreContact::email();
  $tel = \App\Support\StoreContact::phone();
  $telHref = \App\Support\StoreContact::phoneHref();
  $wa = \App\Support\StoreContact::whatsapp();
  $waHref = \App\Support\StoreContact::whatsappHref();
  $date = $notice['date'] ?? null;
  // Served by the middleware, outside the normal controller flow, so the page
  // builds its own metadata. noindex,nofollow: a placeholder must never be
  // indexed in place of the real pages (the response carries the header too).
  $meta = \App\Services\Seo\SeoMeta::noindex(request(), $notice['heading']);
@endphp
<x-layouts.store shell="notice" :meta="$meta">
  <div class="container site-notice">
    <div class="site-notice__card">
      <span class="site-notice__disc">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#{{ $notice['icon'] }}"></use></svg>
      </span>
      <p class="site-notice__eyebrow">{{ $notice['eyebrow'] }}</p>
      <h1>{{ $notice['heading'] }}</h1>
      @if($notice['message'])
        <p class="site-notice__text">{{ $notice['message'] }}</p>
      @endif

      @if($date)
        <p class="site-notice__when">
          <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-clock"></use></svg>
          <span>{{ $notice['dateLabel'] }}
            <time datetime="{{ $date->toIso8601String() }}">{{ $date->format('j F Y') }}</time>
          </span>
        </p>
      @endif

      @if($waHref || $telHref || $email)
        <div class="site-notice__contact">
          <p class="site-notice__contact-label">In the meantime, we are a message away</p>
          <div class="site-notice__actions">
            @if($waHref)
              <a class="btn btn--primary btn--lg" href="{{ $waHref }}" rel="noopener">
                <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-whatsapp"></use></svg>
                WhatsApp {{ $wa }}
              </a>
            @endif
            @if($telHref)
              <a class="btn btn--secondary btn--lg" href="{{ $telHref }}">
                <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-message-circle"></use></svg>
                Call {{ $tel }}
              </a>
            @endif
          </div>
          @if($email)
            <p class="site-notice__email">
              <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-mail"></use></svg>
              <a href="mailto:{{ $email }}">{{ $email }}</a>
            </p>
          @endif
        </div>
      @endif
    </div>
  </div>
</x-layouts.store>
