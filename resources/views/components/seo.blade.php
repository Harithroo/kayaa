@props(['meta' => null])
{{--
  Everything in <head> that search engines and link previews read, from one
  SeoMeta built in the controller.

  Deliberately absent: <meta name="keywords">. Google has ignored it since 2009
  and Bing treats it as a weak spam signal. Do not add it back.
--}}
@php
  // A page that forgets its $meta would otherwise ship a blank title to Google
  // and nobody would notice for weeks. Fail loudly in development instead.
  if (! $meta instanceof \App\Services\Seo\SeoMeta) {
      if (app()->environment('local', 'testing')) {
          throw new \LogicException(
              'This page rendered without $meta. Pass a SeoMeta from the controller '
              .'(SeoMeta::forProduct/forCategory/forPage/noindex).'
          );
      }

      // Production keeps serving the page rather than 500ing over a meta tag.
      $meta = \App\Services\Seo\SeoMeta::forPage('home', request());
  }
@endphp
<title>{{ $meta->title }}</title>
@if($meta->description !== '')
  <meta name="description" content="{{ $meta->description }}">
@endif
<link rel="canonical" href="{{ $meta->canonical }}">
<meta name="robots" content="{{ $meta->robots }}">

<meta property="og:title" content="{{ $meta->ogTitle }}">
@if($meta->ogDescription !== '')
  <meta property="og:description" content="{{ $meta->ogDescription }}">
@endif
<meta property="og:type" content="{{ $meta->ogType }}">
<meta property="og:url" content="{{ $meta->ogUrl }}">
<meta property="og:site_name" content="{{ config('seo.site_name', 'Kayaa') }}">
<meta property="og:locale" content="{{ config('seo.locale', 'en_LK') }}">
@if($meta->ogImage)
  {{-- A product link pasted into WhatsApp or an Instagram DM with no image
       looks broken, and that is this store's main sharing path.

       No og:image:width/height: product photos are 4:5 portrait, not the 1200x630
       a share card wants, and declaring dimensions the file does not have makes
       scrapers crop it badly. Without them they measure the file themselves.
       Set a 1200x630 default share image in Admin > Store settings for the
       pages that have no photo of their own. --}}
  <meta property="og:image" content="{{ $meta->ogImage }}">
  <meta property="og:image:alt" content="{{ $meta->ogTitle }}">
@endif

<meta name="twitter:card" content="{{ $meta->ogImage ? $meta->twitterCard : 'summary' }}">
<meta name="twitter:title" content="{{ $meta->ogTitle }}">
@if($meta->ogDescription !== '')
  <meta name="twitter:description" content="{{ $meta->ogDescription }}">
@endif
@if($meta->ogImage)
  <meta name="twitter:image" content="{{ $meta->ogImage }}">
@endif
