@props(['active' => null])
{{-- Account sidebar, from the prototype's html/account/*. Sign out is a POST
     form, so it is a button styled like the links around it (resources/css/laravel.css). --}}
<nav class="account-nav" aria-label="Account">
  <ul>
    <li><a class="account-nav__link" href="{{ route('account.index') }}" @if($active === 'orders') aria-current="page" @endif>My orders</a></li>
    <li><a class="account-nav__link" href="{{ route('account.reviews') }}" @if($active === 'reviews') aria-current="page" @endif>My reviews</a></li>
    <li><a class="account-nav__link" href="{{ route('account.profile') }}" @if($active === 'profile') aria-current="page" @endif>Profile</a></li>
  </ul>
  <form class="account-nav__logout" method="post" action="{{ route('account.logout') }}">
    @csrf
    <button type="submit" class="account-nav__link account-nav__signout">Sign out</button>
  </form>
</nav>
