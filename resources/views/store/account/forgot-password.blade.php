<x-layouts.store title="Forgot password" nav="account">
  <div class="section">
  <div class="wrap" style="max-width:440px">
    <h1 class="h2 page">Forgot your password?</h1>
    <p class="muted small" style="margin:0 0 20px">Enter your email and we will send a reset link.</p>
    <form method="post" action="{{ route('password.email') }}">
      @csrf
      <x-store.field name="email" label="Email" type="email" autocomplete="email" required />
      <button class="btn dark" style="width:100%">Send reset link</button>
    </form>
    <p class="muted small" style="margin:16px 0 0"><a href="{{ route('account.login') }}">Back to sign in</a></p>
  </div>
  </div>
</x-layouts.store>
