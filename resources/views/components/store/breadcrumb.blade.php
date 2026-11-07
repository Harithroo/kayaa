@props(['trail' => []])
{{--
  The visible breadcrumb, rendered from the trail built by
  App\Support\Seo\Breadcrumbs — the same array JsonLd::breadcrumbs() marks up.

  One source for both is the point: structured data describing a trail the page
  does not show is misleading markup, and two hand-maintained copies drift.

  Each entry is ['name' => string, 'url' => ?string]; a null url is the current
  page and renders as plain text.
--}}
@if(count($trail) > 1)
  <nav {{ $attributes->class(['breadcrumb'])->merge(['aria-label' => 'Breadcrumb']) }}>
    <ol>
      @foreach($trail as $crumb)
        <li @if(! ($crumb['url'] ?? null)) aria-current="page" @endif>
          @if($crumb['url'] ?? null)
            <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
          @else
            {{ $crumb['name'] }}
          @endif
        </li>
      @endforeach
    </ol>
  </nav>
@endif
