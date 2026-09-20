<x-layouts.store title="Create an account" nav="account">
  <div class="section">
  <div class="wrap" style="max-width:440px">
    <h1 class="h2 page">Create an account</h1>
    <p class="muted small" style="margin:0 0 20px">Optional — you can always check out as a guest.</p>
    <form method="post" action="{{ route('account.register') }}">
      @csrf
      <x-store.field name="name" label="Your name" autocomplete="name" required />
      <x-store.field name="email" label="Email" type="email" autocomplete="email" required />
      <x-store.field name="phone" label="Mobile number (optional)" type="tel" inputmode="tel" placeholder="077 412 6690" autocomplete="tel" />
      <x-store.field name="password" label="Password" type="password" autocomplete="new-password" required />
      <x-store.field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
      <button class="btn dark" style="width:100%">Create account</button>
    </form>
    <p class="muted small" style="margin:16px 0 0">Already have one? <a href="{{ route('account.login') }}">Sign in</a>.</p>
  </div>
  </div>
</x-layouts.store>
