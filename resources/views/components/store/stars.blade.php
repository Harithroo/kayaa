@props(['rating' => 0, 'size' => 15])
@php $r = (int) round($rating); @endphp
<span class="stars" style="font-size:{{ $size }}px" role="img" aria-label="{{ number_format($rating, 1) }} out of 5">
  @for($i = 1; $i <= 5; $i++)<span @class(['on' => $i <= $r])>★</span>@endfor
</span>
