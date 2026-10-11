@props(['title', 'heading' => null, 'lede' => null, 'crumb' => null, 'toc' => null, 'page' => null, 'metaDescription' => null])
{{-- The shared shell for the written pages (prototype: html/delivery.html and the
     rest). `toc` is an optional ['#anchor' => 'Label'] list; content.js keeps the
     disclosure open on a wide screen and highlights the section being read. --}}
<x-layouts.store :title="$title" :page="$page" :metaDescription="$metaDescription">
  <div class="container content">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="{{ route('home') }}">Home</a></li>
        <li aria-current="page">{{ $crumb ?? $title }}</li>
      </ol>
    </nav>
    <header class="content__head">
      <h1>{{ $heading ?? $title }}</h1>
      @if($lede)<p class="content__lede">{{ $lede }}</p>@endif
    </header>
    <div @class(['content__layout', 'content__layout--toc' => (bool) $toc])>
      @if($toc)
        <details class="toc" data-toc open>
          <summary class="toc__summary">On this page <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-down"></use></svg></summary>
          <ol class="toc__list" role="list">
            @foreach($toc as $anchor => $label)
              <li><a href="{{ $anchor }}">{{ $label }}</a></li>
            @endforeach
          </ol>
        </details>
      @endif
      <div class="content__main">
        <div class="prose">
          {{ $slot }}
        </div>
        {{-- Anything that sits outside the prose column, such as the help card. --}}
        {{ $after ?? '' }}
      </div>
    </div>
  </div>
</x-layouts.store>
