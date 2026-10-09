@php
  $navPath = trim((string) preg_replace('#^(de|fr)(/|$)#', '', trim(request()->path(), '/')), '/');
  $navItems = [
    ['path' => '', 'icon' => 'nav-home', 'width' => 22, 'height' => 22, 'label' => 'Home', 'active' => $navPath === ''],
    ['path' => 'free-slots', 'icon' => 'nav-slots', 'width' => 18, 'height' => 24, 'label' => 'Slots', 'active' => $navPath === 'free-slots' || str_starts_with($navPath, 'slots/')],
    ['path' => 'bonuses', 'icon' => 'nav-bonuses', 'width' => 24, 'height' => 24, 'label' => 'Bonuses', 'active' => $navPath === 'bonuses'],
  ];
@endphp
<nav class="bottom-nav" aria-label="Mobile primary">
  @foreach($navItems as $item)
    <a class="bottom-nav__link {{ $item['active'] ? 'bottom-nav__link--active' : '' }}" href="{{ localized_url(null, $item['path']) }}" @if($item['active']) aria-current="page" @endif>
      <span class="bottom-nav__icon"><x-site-icon :name="$item['icon']" :width="$item['width']" :height="$item['height']" /></span>
      <span class="bottom-nav__label">{{ $item['label'] }}</span>
    </a>
  @endforeach
  <button class="bottom-nav__link js-open-search" type="button" aria-haspopup="dialog" aria-controls="site-search">
    <span class="bottom-nav__icon"><x-site-icon name="nav-search" width="21.5228" height="22.0007" /></span>
    <span class="bottom-nav__label">Search</span>
  </button>
</nav>
