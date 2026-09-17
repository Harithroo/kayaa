@props(['banner'])
<div class="promo {{ $banner->style }}">
  <div class="promo-copy">
    @if($banner->eyebrow)<p class="eyebrow">{{ $banner->eyebrow }}</p>@endif
    <h2>{{ $banner->title }}</h2>
    @if($banner->body)<p>{{ $banner->body }}</p>@endif
  </div>
  @if($banner->cta_label && $banner->cta_url)
    <a class="btn" href="{{ $banner->cta_url }}">{{ $banner->cta_label }}</a>
  @endif
</div>
