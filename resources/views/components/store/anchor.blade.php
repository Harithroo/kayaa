@props(['id', 'label'])
{{-- The self-link beside a section heading (prototype: the content pages). --}}
<a class="anchor" href="#{{ $id }}" aria-label="Link to this section: {{ $label }}">
  <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-link"></use></svg>
</a>
