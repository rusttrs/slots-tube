<nav class="bottom-nav" aria-label="Mobile primary">
  <a class="bottom-nav__link {{ request()->is('/') || request()->is('de') || request()->is('fr') ? 'bottom-nav__link--active' : '' }}" href="{{ localized_url(null, '') }}">
    <span class="bottom-nav__label">Home</span>
  </a>
  <a class="bottom-nav__link" href="{{ localized_url(null, 'free-slots') }}">
    <span class="bottom-nav__label">Free Slots</span>
  </a>
  <a class="bottom-nav__link" href="{{ localized_url(null, 'bonuses') }}">
    <span class="bottom-nav__label">Bonuses</span>
  </a>
  @guest
    <button class="bottom-nav__link js-open-auth" type="button" aria-haspopup="dialog" aria-controls="auth-modal"><span class="bottom-nav__label">Login</span></button>
  @else
    <a class="bottom-nav__link" href="{{ localized_url(null, 'profile') }}"><span class="bottom-nav__label">Profile</span></a>
  @endguest
</nav>
