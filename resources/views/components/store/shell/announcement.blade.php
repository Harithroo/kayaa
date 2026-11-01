@props(['topbar' => null, 'freeOver' => 0, 'islandDays' => null])
{{-- tools/partials/announcement.html. Admin > Banners drives it; without a live
     topbar banner the delivery line from the store config stands in. --}}
<section class="topbar topbar--lilac" data-topbar aria-label="Announcement">
  <p class="topbar__text">
    @if($topbar)
      <span>@if($topbar->cta_url)<a href="{{ $topbar->cta_url }}">{{ $topbar->title }}</a>@else{{ $topbar->title }}@endif</span>
    @else
      <span>Island-wide delivery in {{ $islandDays }} days</span>
      @if($freeOver > 0)<span class="topbar__extra"> · Free over {{ money($freeOver) }}</span>@endif
    @endif
  </p>
  <button type="button" class="topbar__close" data-topbar-dismiss aria-label="Dismiss announcement">
    <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-x"></use></svg>
  </button>
</section>
