@props(['product'])
@php
  $tints = ['butter','sage','cloud','blush','sand'];
  $tint = $tints[$product->id % count($tints)];
  $colors = $product->colors()->count();
  $meta = collect([$colors ? "$colors colour".($colors > 1 ? 's' : '') : null, $product->sizeRangeLabel()])->filter()->implode(' · ');
  $was = $product->compareAtPrice();
@endphp
<a class="card" href="{{ route('products.show', $product) }}">
  <x-store.photo :image="$product->primaryImage()" :tint="$tint" :alt="$product->name">
    @if($was)<span class="badge clay">Sale</span>
    @elseif($product->is_new)<span class="badge">New</span>
    @elseif($product->isLowStock())<span class="badge clay">Low stock</span>
    @elseif($product->totalStock() === 0)<span class="badge">Sold out</span>@endif
  </x-store.photo>
  <span class="cardname">{{ $product->name }}</span>
  @if($meta)<span class="cardmeta">{{ $meta }}</span>@endif
  <span class="cardrow"><span class="price">{{ money($product->minPrice()) }}</span>@if($was)<span class="was">{{ money($was) }}</span>@endif</span>
</a>
