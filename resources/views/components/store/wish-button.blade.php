@props(['product'])
{{-- Heart toggle. A real form so it works without JS; app.js upgrades it to an in-place fetch. --}}
@php $saved = app(\App\Services\WishlistService::class)->has($product->id); @endphp
<form method="post" action="{{ route('wishlist.toggle', $product) }}" data-wish {{ $attributes->class(['wishform']) }}>
  @csrf
  <button type="submit" class="wishbtn {{ $saved ? 'on' : '' }}" aria-pressed="{{ $saved ? 'true' : 'false' }}"
          aria-label="{{ $saved ? 'Remove '.$product->name.' from wishlist' : 'Save '.$product->name.' to wishlist' }}">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="{{ $saved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20S5 15.4 5 10.7A3.7 3.7 0 0 1 12 8a3.7 3.7 0 0 1 7 2.7C19 15.4 12 20 12 20Z"/></svg>
  </button>
</form>
