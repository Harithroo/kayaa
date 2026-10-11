{{-- Our story, in the design system's content shell (prototype: html/about.html). --}}
<x-store.content-page title="Our story" heading="Clothes for the first two years, made for this climate." crumb="Our story" page="about"
  lede="Kayaa started in Colombo with a simple problem: most baby clothes sold here are cut for cold countries.">
  <p>Heavy knits, closed necks, layers a Colombo afternoon does not need. We wanted something softer, lighter and sized so you order once.</p>
  <p>We work with local knitters and a small cut-and-sew team to make lightweight cotton and bamboo basics — bodysuits, sleepsuits, sleep bags and sets sized by age band rather than guesswork. Every fabric is washed before it ships, so it arrives soft.</p>
  <p><em>This copy is placeholder — replace with the real story, founding year and team.</em></p>

  <ul class="trust__list" role="list">
    <li class="trust__item">
      <span class="trust__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-ruler"></use></svg></span>
      <div><p class="trust__title">Sized by age</p><p class="trust__text">Age bands with real height and weight ranges, so you order once.</p></div>
    </li>
    <li class="trust__item">
      <span class="trust__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-package"></use></svg></span>
      <div><p class="trust__title">Made nearby</p><p class="trust__text">Knitted and sewn with small teams in the Western Province.</p></div>
    </li>
    <li class="trust__item">
      <span class="trust__icon"><svg class="icon icon--24" aria-hidden="true" focusable="false"><use href="#i-shield-check"></use></svg></span>
      <div><p class="trust__title">Honest fabric</p><p class="trust__text">Soft cotton and bamboo, pre-washed before it ships.</p></div>
    </li>
  </ul>

  <div class="cat-grid">
    <x-store.photo :image="null" alt="" :tone="1" ratio="4 / 3" />
    <x-store.photo :image="null" alt="" :tone="3" ratio="4 / 3" />
    <x-store.photo :image="null" alt="" :tone="2" ratio="4 / 3" />
  </div>
</x-store.content-page>
