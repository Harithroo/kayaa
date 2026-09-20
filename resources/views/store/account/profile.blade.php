<x-layouts.store title="My details" nav="account">
  <div class="section">
  <div class="wrap" style="max-width:520px">
    <h1 class="h2 page">My details</h1>
    <x-store.account-nav active="profile" />

    <form method="post" action="{{ route('account.profile.update') }}" class="panel" style="margin-bottom:20px">
      @csrf @method('patch')
      <h2 class="h3" style="font-size:17px">Contact details</h2>
      <x-store.field name="name" label="Your name" :value="$user->name" required />
      <x-store.field name="email" label="Email" type="email" :value="$user->email" required />
      <x-store.field name="phone" label="Mobile number" type="tel" inputmode="tel" :value="$user->phone" />
      <button class="btn dark" style="width:100%">Save details</button>
    </form>

    <form method="post" action="{{ route('account.password.update') }}" class="panel">
      @csrf @method('patch')
      <h2 class="h3" style="font-size:17px">Change password</h2>
      <x-store.field name="current_password" label="Current password" type="password" autocomplete="current-password" required />
      <x-store.field name="password" label="New password" type="password" autocomplete="new-password" required />
      <x-store.field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
      <button class="btn dark" style="width:100%">Change password</button>
    </form>
  </div>
  </div>
</x-layouts.store>
