<header class="header">
  <div class="container header__inner">
    <div class="header__top">
      <a class="logo" href="{{ localized_url(null, '') }}" aria-label="Slots.tube home">
        <img class="logo__icon" src="/assets/images/header/1a6dc.svg" alt="" width="43" height="30" />
        <span class="logo__text">slots.tube</span>
      </a>

      <form class="header__search" role="search" action="{{ localized_url(null, 'free-slots') }}" method="get">
        <x-site-icon name="search" class="header__search-icon" width="20" height="20" />
        <input class="header__search-input" type="search" name="q" placeholder="Search..." aria-label="Search" />
      </form>

      <div class="header__actions">
        @guest
          <button class="header__user header__user--auth js-open-auth" type="button" aria-label="{{ __('slot.log_in') }}" aria-haspopup="dialog" aria-controls="auth-modal" id="auth-open">
            <x-site-icon name="user" width="18" height="18" />
          </button>
        @else
          <div class="header__user-wrap">
            <button class="header__user header__user--avatar" type="button" aria-label="Open profile menu" aria-expanded="false" aria-controls="profile-menu" id="profile-toggle">
              <img src="{{ auth()->user()->avatarUrl() }}" alt="" width="40" height="40" />
            </button>
            <div class="header__menu" id="profile-menu" hidden role="menu" aria-labelledby="profile-toggle">
              <div class="header__menu-head">
                <img class="header__menu-avatar" src="{{ auth()->user()->avatarUrl() }}" alt="" width="42" height="42" />
                <div class="header__menu-meta">
                  <div class="header__menu-name-row">
                    <span class="header__menu-name">{{ auth()->user()->displayName() }}</span>
                  </div>
                  <span class="header__menu-email">{{ auth()->user()->email }}</span>
                </div>
              </div>
              <div class="header__menu-list" role="none">
                <a class="header__menu-item" href="{{ localized_url(null, 'profile') }}" role="menuitem">
                  <x-site-icon name="settings" width="20" height="20" />
                  <span>Profile Settings</span>
                </a>
              </div>
              <form action="{{ url('/logout') }}" method="post" role="none">
                @csrf
                <button class="header__menu-item header__menu-item--logout" type="submit" role="menuitem" style="width:100%;background:none;border:0;cursor:pointer;display:flex;align-items:center;gap:8px;">
                  <x-site-icon name="logout" width="20" height="20" />
                  <span>Logout</span>
                </button>
              </form>
            </div>
          </div>
        @endguest

        @php
          $locales = ['en' => 'English', 'de' => 'German', 'fr' => 'French'];
          $current = app()->getLocale();
        @endphp
        <div class="header__lang-wrap">
          <button class="header__lang" type="button" id="lang-toggle" aria-label="Language: {{ $locales[$current] ?? 'English' }}" aria-expanded="false" aria-haspopup="menu" aria-controls="lang-menu">
            <span class="header__lang-code">{{ strtoupper($current) }}</span>
            <x-site-icon name="lang-chevron" class="header__lang-chevron" width="14" height="14" />
          </button>
          <div class="header__lang-menu" id="lang-menu" hidden role="menu" aria-labelledby="lang-toggle">
            <div class="header__lang-menu-grid">
              @foreach($locales as $code => $label)
                <a class="header__lang-option {{ $current === $code ? 'is-active' : '' }}" href="{{ localized_url($code) }}" role="menuitem" @if($current===$code) aria-current="true" @endif data-lang="{{ $code }}">{{ $label }}</a>
              @endforeach
            </div>
          </div>
        </div>

        <button class="header__burger" type="button" aria-label="{{ __('slot.open_menu') }}" aria-expanded="false" aria-controls="mobile-drawer" id="menu-toggle">
          <x-site-icon name="menu-more" width="20" height="20" />
        </button>
      </div>
    </div>

    <nav class="header__nav-row" aria-label="{{ __('slot.primary_nav') }}">
      <a class="header__nav-link" href="{{ localized_url(null, 'free-slots') }}">{{ __('slot.slots') }}</a>
      <a class="header__nav-link" href="{{ localized_url(null, 'bonuses') }}">{{ __('slot.nav_bonuses') }}</a>
      <div class="header__nav-dd">
        <a class="header__nav-link header__nav-link--dropdown" href="{{ rtrim(localized_url(null, 'authors'), '/') }}/" aria-haspopup="true">
          {{ __('slot.nav_about') }}
          <x-site-icon name="nav-chevron" class="header__nav-chevron" width="20" height="20" />
        </a>
        <div class="header__nav-panel">
          <a class="header__nav-panel-link" href="{{ rtrim(localized_url(null, 'authors'), '/') }}/">{{ __('slot.nav_team') }}</a>
          <a class="header__nav-panel-link" href="{{ rtrim(localized_url(null, 'our-mission'), '/') }}/">{{ __('slot.nav_mission') }}</a>
        </div>
      </div>
      <a class="header__nav-link" href="{{ localized_url(null, 'content') }}">{{ __('slot.nav_news') }}</a>
    </nav>
  </div>

  <nav class="header__drawer" id="mobile-drawer" aria-label="{{ __('slot.mobile_nav') }}" hidden>
    <div class="header__drawer-top">
      <button class="header__drawer-close" type="button" id="drawer-close" aria-label="{{ __('slot.close_menu') }}">
        <x-site-icon name="drawer-close" width="24" height="24" />
      </button>
      <div class="header__drawer-top-actions">
        <div class="header__lang-wrap header__lang-wrap--drawer">
          <button class="header__drawer-lang" type="button" id="drawer-lang-toggle" aria-label="Language: {{ $locales[$current] ?? 'English' }}" aria-expanded="false" aria-haspopup="menu" aria-controls="drawer-lang-menu">
            <span class="header__drawer-lang-code">{{ strtoupper($current) }}</span>
            <x-site-icon name="lang-chevron" class="header__lang-chevron" width="14" height="14" />
          </button>
          <div class="header__lang-menu header__lang-menu--drawer" id="drawer-lang-menu" hidden role="menu" aria-labelledby="drawer-lang-toggle">
            <div class="header__lang-menu-grid">
              @foreach($locales as $code => $label)
                <a class="header__lang-option {{ $current === $code ? 'is-active' : '' }}" href="{{ localized_url($code) }}" role="menuitem" @if($current===$code) aria-current="true" @endif data-lang="{{ $code }}">{{ $label }}</a>
              @endforeach
            </div>
          </div>
        </div>
        @guest
          <button class="header__drawer-auth js-open-auth" type="button" aria-label="{{ __('slot.log_in') }}" aria-haspopup="dialog" aria-controls="auth-modal">
            <x-site-icon name="user" width="18" height="18" />
          </button>
        @endguest
      </div>
    </div>

    <form class="header__drawer-search" role="search" action="{{ localized_url(null, 'free-slots') }}" method="get">
      <x-site-icon name="search" class="header__search-icon" width="20" height="20" />
      <input class="header__search-input" type="search" name="q" placeholder="Search..." aria-label="Search" />
    </form>

    <div class="header__drawer-nav">
      <a class="header__drawer-link" href="{{ localized_url(null, 'free-slots') }}">
        <x-site-icon name="drawer-slots" width="24" height="24" />
        <span>Slots</span>
      </a>
      <a class="header__drawer-link" href="{{ localized_url(null, 'bonuses') }}">
        <x-site-icon name="drawer-gift" width="24" height="24" />
        <span>Bonuses</span>
      </a>
      <div class="header__drawer-dd">
        <button class="header__drawer-link header__drawer-link--dropdown" type="button" aria-expanded="false" aria-controls="drawer-about">
          <x-site-icon name="drawer-info" width="24" height="24" />
          <span>{{ __('slot.nav_about') }}</span>
          <x-site-icon name="nav-chevron" class="header__drawer-chevron" width="11" height="11" />
        </button>
        <div class="header__drawer-panel" id="drawer-about" hidden>
          <a class="header__drawer-sublink" href="{{ rtrim(localized_url(null, 'authors'), '/') }}/">{{ __('slot.nav_team') }}</a>
          <a class="header__drawer-sublink" href="{{ rtrim(localized_url(null, 'our-mission'), '/') }}/">{{ __('slot.nav_mission') }}</a>
        </div>
      </div>
      <a class="header__drawer-link" href="{{ localized_url(null, 'content') }}">
        <x-site-icon name="drawer-news" width="24" height="24" />
        <span>News</span>
      </a>
    </div>
  </nav>
</header>
