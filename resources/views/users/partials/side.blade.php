{{-- $user, $active: settings|activity|null, $subtitle, $stats (optional) --}}
<aside class="profile-side" aria-label="{{ __('profile.account') }}">
  <div class="profile-card {{ empty($stats) ? 'profile-card--compact' : '' }}">
    <div class="profile-card__top">
      @include('users.partials.avatar', ['user' => $user])
      <div class="profile-card__meta">
        <{{ $nameTag ?? 'p' }} class="profile-card__name">{{ $user->displayName() }}</{{ $nameTag ?? 'p' }}>
        @if(filled($subtitle ?? null))
          <p class="profile-card__email">{{ $subtitle }}</p>
        @endif
      </div>
    </div>
    @if(! empty($stats))
      <div class="profile-card__level-labels">
        @foreach($stats as $stat)
          <span>{{ $stat }}</span>
        @endforeach
      </div>
    @endif
  </div>

  @if($active)
    <nav class="profile-menu" aria-label="{{ __('profile.sections') }}">
      <a class="profile-menu__link {{ $active === 'settings' ? 'profile-menu__link--active' : '' }}" href="{{ localized_url(null, 'profile') }}" @if($active === 'settings') aria-current="page" @endif>
        <x-site-icon name="settings" width="18" height="20" />
        <span>{{ __('profile.menu_settings') }}</span>
      </a>
      <a class="profile-menu__link {{ $active === 'activity' ? 'profile-menu__link--active' : '' }}" href="{{ $user->publicUrl() }}" @if($active === 'activity') aria-current="page" @endif>
        <x-site-icon name="profile-activity" width="18" height="16" />
        <span>{{ __('profile.menu_activity') }}</span>
      </a>
      <form class="profile-menu__logout" action="{{ url('/logout') }}" method="post">
        @csrf
        <button class="profile-menu__link profile-menu__link--logout" type="submit">
          <x-site-icon name="logout" width="18" height="18" />
          <span>{{ __('profile.menu_logout') }}</span>
        </button>
      </form>
    </nav>
  @endif
</aside>
