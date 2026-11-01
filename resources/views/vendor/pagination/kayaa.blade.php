{{-- The design system's pagination (prototype: shop.html). Laravel's paginator,
     rendered in the prototype's markup so the listing keeps its look. --}}
@if ($paginator->hasPages())
  <nav class="pagination" aria-label="Pagination" data-listing-pagination>
    <ul class="pagination__list" role="list">
      <li>
        @if ($paginator->onFirstPage())
          <span class="pagination__link pagination__edge" aria-disabled="true">
            <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-left"></use></svg><span class="pagination__edge-label">Prev</span>
          </span>
        @else
          <a class="pagination__link pagination__edge" href="{{ $paginator->previousPageUrl() }}" rel="prev">
            <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-left"></use></svg><span class="pagination__edge-label">Prev</span>
          </a>
        @endif
      </li>

      @foreach ($elements as $element)
        @if (is_string($element))
          <li class="pagination__num"><span class="pagination__link" aria-hidden="true">{{ $element }}</span></li>
        @endif
        @if (is_array($element))
          @foreach ($element as $page => $url)
            <li class="pagination__num">
              <a class="pagination__link" href="{{ $url }}" @if($page == $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</a>
            </li>
          @endforeach
        @endif
      @endforeach

      <li class="pagination__status">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</li>

      <li>
        @if ($paginator->hasMorePages())
          <a class="pagination__link pagination__edge" href="{{ $paginator->nextPageUrl() }}" rel="next">
            <span class="pagination__edge-label">Next</span><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-right"></use></svg>
          </a>
        @else
          <span class="pagination__link pagination__edge" aria-disabled="true">
            <span class="pagination__edge-label">Next</span><svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-chevron-right"></use></svg>
          </span>
        @endif
      </li>
    </ul>
  </nav>
@endif
