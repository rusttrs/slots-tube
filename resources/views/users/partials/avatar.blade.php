<div class="profile-card__avatar {{ $class ?? '' }} {{ $user->hasAvatar() ? 'has-photo' : '' }}" aria-hidden="true">
  @if($user->hasAvatar())
    <img src="{{ $user->avatarUrl() }}" alt="" width="130" height="130" />
  @else
    {{ $user->initials() }}
  @endif
</div>
