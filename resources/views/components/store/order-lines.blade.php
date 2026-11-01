@props(['lines', 'cart' => null, 'order' => null])
{{-- The order summary block shared by checkout (twice: the mobile disclosure and
     the aside) and the thank-you and order pages. Prototype: checkout.html. --}}
<ul class="order-lines" role="list" data-summary-lines>
  @foreach($lines as $line)
    @php
      // Cart lines carry a variant; order items carry their own copy of the data.
      $v = $line->variant ?? null;
      $product = $v?->product ?? $line->product ?? null;
      $name = $product?->name ?? $line->product_name ?? '';
      $meta = $v?->label() ?? $line->variant_label ?? null;
      $total = $line->lineTotal ?? $line->line_total ?? 0;
      $seed = $product?->id ?? $loop->index;
    @endphp
    <li class="order-line">
      <x-store.photo class="media--tile order-line__media" :image="$product?->primaryImage()" :alt="$name" :seed="$seed" ratio="1 / 1" />
      <div>
        <p class="order-line__name">{{ $name }}</p>
        <p class="order-line__meta">{{ collect([$meta, 'Qty '.$line->qty])->filter()->join(' · ') }}</p>
      </div>
      <p class="order-line__price">{{ money($total) }}</p>
    </li>
  @endforeach
</ul>
@php
  $subtotal = $cart ? $cart->subtotal() : ($order?->subtotal ?? 0);
  $shipping = $cart ? $cart->shipping() : ($order?->shipping ?? 0);
  $grand = $cart ? $cart->total() : ($order?->total ?? 0);
@endphp
<dl class="totals">
  <div class="totals__row"><dt>Subtotal</dt><dd data-summary-subtotal>{{ money($subtotal) }}</dd></div>
  <div class="totals__row"><dt>Delivery</dt><dd data-summary-delivery>{{ $shipping === 0 ? 'Free' : money($shipping) }}</dd></div>
  <div class="totals__row totals__row--total"><dt>Total</dt><dd data-summary-total>{{ money($grand) }}</dd></div>
</dl>
<p class="eta-line" data-eta-summary hidden></p>
@if($cart)
  <a class="link-arrow summary-edit" href="{{ route('cart.index') }}">Edit bag <svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a>
@endif
