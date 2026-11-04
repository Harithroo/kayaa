@props(['data' => null])
{{--
  One or more schema.org blocks, built by App\Services\Seo\JsonLd.

  Accepts a single array or a list of them, and skips nulls so a caller can pass
  a builder's output straight through without checking it first.

  JSON_UNESCAPED_SLASHES keeps schema.org URLs readable; JSON_UNESCAPED_UNICODE
  keeps size labels like "0–3m" intact rather than as – escapes. Blade's
  {!! !!} is safe here because json_encode escapes the sequences that could
  break out of a <script> block.
--}}
@php
  $blocks = collect(is_array($data) && array_is_list($data) ? $data : [$data])
      ->filter(fn ($block) => is_array($block) && $block !== []);
@endphp
@foreach($blocks as $block)
  <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endforeach
