<x-layouts.store title="Size guide">
  <div class="section">
  <div class="wrap prose">
    <p class="eyebrow">Sized by age</p>
    <h1 style="font-size:28px">Size guide</h1>
    <p>Our sizes follow age bands, but every baby is different — height and weight are the better guide. Between two sizes? Go up. Babies grow through a band in about six weeks and a slightly roomy fit is more comfortable in the heat.</p>
    @if($scale)
      <table class="sizetable" >
        <thead><tr><th>Size</th><th>Height</th><th>Weight</th></tr></thead>
        <tbody>
          @foreach($scale->options as $opt)
            <tr><td>{{ $opt->label }}</td><td>{{ $opt->heightRange() ?? '—' }}</td><td>{{ $opt->weightRange() ?? '—' }}</td></tr>
          @endforeach
        </tbody>
      </table>
    @endif
    <h2>How to measure</h2>
    <p>Lay your baby flat and measure from the top of the head to the heel. Weight from the last clinic visit is fine. If you're buying a gift and don't know either, size by age and choose something with a bit of stretch, like a ribbed bodysuit.</p>
  </div>
  </div>
</x-layouts.store>
