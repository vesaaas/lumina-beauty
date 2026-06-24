@props(['eyebrow', 'title', 'copy' => null])

<section class="page-heading" data-scroll-reveal>
  <p class="eyebrow">{{ $eyebrow }}</p>
  <h1>{{ $title }}</h1>
  @if ($copy)
    <p>{{ $copy }}</p>
  @endif
</section>
