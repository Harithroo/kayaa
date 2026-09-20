<x-layouts.store title="Reset password" nav="account">
  <div class="section">
  <div class="wrap" style="max-width:440px">
    <h1 class="h2 page">Choose a new password</h1>
    <form method="post" action="{{ route('password.update') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <x-store.field name="email" label="Email" type="email" :value="$email" required />
      <x-store.field name="password" label="New password" type="password" autocomplete="new-password" required />
      <x-store.field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
      <button class="btn dark" style="width:100%">Save new password</button>
    </form>
  </div>
  </div>
</x-layouts.store>
