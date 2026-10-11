@props(['cart', 'cartCount' => 0])
{{-- tools/partials/cart-drawer.html. The inner .cart-panel is exactly what
     GET /cart/panel returns, so the drawer can be refreshed on its own. --}}
<div class="drawer drawer--right" id="cart-drawer" data-layer="cart" data-cart-drawer role="dialog" aria-modal="true" aria-labelledby="cart-title" aria-hidden="true">
  <x-store.cart-panel :cart="$cart" :cart-count="$cartCount" />
</div>
