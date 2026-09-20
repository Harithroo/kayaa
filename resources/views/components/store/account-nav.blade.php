@props(['active' => 'orders'])
<nav class="accnav" aria-label="Account">
  <a href="{{ route('account.index') }}" @class(['on' => $active === 'orders'])>Orders</a>
  <a href="{{ route('account.reviews') }}" @class(['on' => $active === 'reviews'])>My reviews</a>
  <a href="{{ route('account.profile') }}" @class(['on' => $active === 'profile'])>Details</a>
  <form method="post" action="{{ route('account.logout') }}">@csrf<button type="submit">Sign out</button></form>
</nav>
