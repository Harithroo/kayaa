<x-layouts.store title="Sign in" nav="account">
  <div class="section">
  <div class="wrap" style="max-width:440px">
    <h1 class="h2 page">Sign in</h1>
    <p class="muted small" style="margin:0 0 20px">Track orders, keep your details and review what you have bought.</p>
    <form method="post" action="{{ route('account.login') }}">
      @csrf
      <x-store.field name="email" label="Email" type="email" autocomplete="email" required />
      <x-store.field name="password" label="Password" type="password" autocomplete="current-password" required />
      <label class="check"><input type="checkbox" name="remember" value="1" checked> Keep me signed in</label>
      <button class="btn dark" style="width:100%">Sign in</button>
    </form>
    <p class="muted small" style="margin:16px 0 0">
      <a href="{{ route('password.request') }}">Forgot your password?</a>
    </p>
    <p class="muted small" style="margin:6px 0 0">
      New here? <a href="{{ route('account.register') }}">Create an account</a> — or
      <a href="{{ route('orders.track') }}">track an order without one</a>.
    </p>
  </div>
  </div>
</x-layouts.store>
