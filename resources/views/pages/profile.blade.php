@extends('layouts.app')

@section('title', __('profile.title').' | slots.tube')
@section('robots', 'noindex, nofollow')

@php
  $nicknameLockedUntil = $user->nicknameChangeAvailableAt();
  $saved = session('profile_saved');
  $openAvatarModal = $errors->avatar->any();
@endphp

@section('content')
  <div class="container">
    @if(session('email_changed'))
      <p class="profile-flash" role="status">{{ __('profile.email_changed') }}</p>
    @endif

    <div class="profile-layout">
      @include('users.partials.side', ['user' => $user, 'active' => 'settings', 'subtitle' => $user->email])

      <div class="profile-main" id="settings">
        <section class="profile-panel profile-panel--avatar">
          <div class="profile-panel__head">
            <h2 class="profile-panel__title">{{ __('profile.picture_title') }}</h2>
          </div>
          <div class="profile-panel__row">
            @include('users.partials.avatar', ['user' => $user, 'class' => 'profile-card__avatar--lg'])
            <div class="profile-panel__avatar-actions">
              <p class="profile-panel__label">{{ __('profile.change_avatar') }}</p>
              <button
                class="profile-panel__update js-open-avatar"
                type="button"
                aria-haspopup="dialog"
                aria-controls="avatar-modal"
              >
                {{ __('profile.update') }}
              </button>
            </div>
          </div>
          <p class="profile-panel__hint profile-panel__hint--avatar">{{ __('profile.avatar_hint') }}</p>
          @if($saved === 'avatar')
            <p class="profile-panel__status profile-panel__status--avatar" role="status">{{ __('profile.saved') }}</p>
          @endif
        </section>

        <section class="profile-panel">
          <form method="post" action="{{ localized_url(null, 'profile/username') }}">
            @csrf
            <div class="profile-panel__head">
              <h2 class="profile-panel__title">{{ __('profile.username_title') }}</h2>
              <button class="profile-panel__save" type="submit" @disabled($nicknameLockedUntil)>
                <x-site-icon name="profile-save" width="19" height="19" />
                {{ __('profile.save') }}
              </button>
            </div>
            <p class="profile-panel__hint profile-panel__hint--tight">
              {{ $nicknameLockedUntil ? __('profile.username_locked', ['date' => $nicknameLockedUntil->translatedFormat('F j, Y')]) : __('profile.username_hint') }}
            </p>
            <label class="profile-field">
              <span class="profile-field__label">{{ __('profile.username_label') }}</span>
              <input
                class="profile-field__input"
                type="text"
                name="nickname"
                value="{{ old('nickname', $user->nickname ?: $user->displayName()) }}"
                placeholder="{{ __('profile.username_placeholder') }}"
                minlength="2"
                maxlength="40"
                autocomplete="nickname"
                required
                @readonly($nicknameLockedUntil)
              />
            </label>
            @error('nickname', 'username')
              <p class="profile-panel__error" role="alert">{{ $message }}</p>
            @enderror
            @if($saved === 'username')
              <p class="profile-panel__status" role="status">{{ __('profile.saved') }}</p>
            @endif
          </form>
        </section>

        <section class="profile-panel profile-panel--email">
          <form method="post" action="{{ localized_url(null, 'profile/email') }}">
            @csrf
            <div class="profile-panel__head">
              <h2 class="profile-panel__title">{{ __('profile.email_title') }}</h2>
              <button class="profile-panel__save" type="submit">
                <x-site-icon name="profile-save" width="19" height="19" />
                {{ __('profile.save') }}
              </button>
            </div>
            <p class="profile-panel__hint">{{ __('profile.email_hint') }}</p>
            <div class="profile-field-row">
              <label class="profile-field">
                <span class="profile-field__label">{{ __('profile.email_label') }}</span>
                <input class="profile-field__input" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('profile.email_placeholder') }}" autocomplete="email" maxlength="255" required />
              </label>
              <label class="profile-field">
                <span class="profile-field__label">{{ __('profile.email_repeat') }}</span>
                <input class="profile-field__input" type="email" name="email_confirmation" value="" placeholder="{{ __('profile.email_placeholder') }}" autocomplete="email" maxlength="255" required />
              </label>
            </div>
            @error('email', 'email')
              <p class="profile-panel__error" role="alert">{{ $message }}</p>
            @enderror
            @if(session('email_change_sent'))
              <p class="profile-panel__status" role="status">{{ __('profile.email_sent', ['email' => session('email_change_sent')]) }}</p>
            @endif
          </form>
        </section>
      </div>
    </div>
  </div>

  <div
    class="avatar-modal"
    id="avatar-modal"
    hidden
    aria-modal="true"
    aria-labelledby="avatar-modal-title"
    role="dialog"
    data-avatar-presets='@json(\App\Models\User::AVATAR_PRESETS)'
    data-avatar-too-big="{{ __('profile.avatar_too_big') }}"
    @if($openAvatarModal) data-auto-open @endif
  >
    <button class="avatar-modal__backdrop js-close-avatar" type="button" tabindex="-1" aria-label="{{ __('profile.close') }}"></button>
    <form class="avatar-modal__dialog" method="post" action="{{ localized_url(null, 'profile') }}" enctype="multipart/form-data" data-avatar-form>
      @csrf
      <input type="hidden" name="avatar_preset" value="" data-avatar-preset />
      <button class="avatar-modal__close js-close-avatar" type="button" aria-label="{{ __('profile.close') }}">
        <x-site-icon name="profile-close" width="30" height="30" />
      </button>

      <div class="avatar-modal__logo" aria-hidden="true">
        <img src="/assets/images/header/1a6dc.svg" alt="" width="43" height="30" />
        <span>slots.tube</span>
      </div>

      <h2 class="visually-hidden" id="avatar-modal-title">{{ __('profile.modal_title') }}</h2>

      <div class="avatar-modal__box" data-avatar-view="idle">
        <div class="avatar-modal__idle">
          <div class="avatar-modal__preview avatar-modal__preview--initials" data-avatar-preview aria-hidden="true">
            @if($user->hasAvatar())
              <img class="avatar-modal__photo" src="{{ $user->avatarUrl() }}" alt="" width="80" height="80" />
            @else
              {{ $user->initials() }}
            @endif
          </div>
          <button class="avatar-modal__change js-avatar-edit" type="button">
            <span>{{ __('profile.change_avatar') }}</span>
            <x-site-icon name="profile-edit" width="20" height="20" />
          </button>
        </div>

        <div class="avatar-modal__edit">
          <div class="avatar-modal__edit-row">
            <div class="avatar-modal__preview avatar-modal__preview--photo is-initials" data-avatar-edit-preview aria-hidden="true">
              <img class="avatar-modal__photo" data-avatar-photo src="/{{ \App\Models\User::AVATAR_PRESETS[0] }}" alt="" width="80" height="80" />
              <span class="avatar-modal__initials" data-avatar-initials>{{ $user->initials() }}</span>
            </div>
            <div class="avatar-modal__actions">
              <button class="avatar-modal__btn avatar-modal__btn--random js-avatar-random" type="button">
                <x-site-icon name="profile-refresh" width="20" height="20" />
                <span>{{ __('profile.random_avatar') }}</span>
              </button>
              <label class="avatar-modal__btn avatar-modal__btn--upload">
                <x-site-icon name="profile-upload" width="20" height="20" />
                <span>{{ __('profile.upload_avatar') }}</span>
                <input class="avatar-modal__file" id="avatar-upload-input" type="file" name="avatar" accept="image/jpeg,image/jpg,image/png" hidden />
              </label>
            </div>
          </div>
          <p class="avatar-modal__hint">{{ __('profile.avatar_choose') }}</p>
        </div>
      </div>

      @error('avatar', 'avatar')
        <p class="profile-panel__error" role="alert">{{ $message }}</p>
      @enderror

      <label class="avatar-modal__field">
        <span class="avatar-modal__field-label">{{ __('profile.nickname') }}</span>
        <input
          class="avatar-modal__input"
          type="text"
          name="nickname"
          value="{{ old('nickname', $user->nickname ?: $user->displayName()) }}"
          minlength="2"
          maxlength="40"
          autocomplete="nickname"
          required
          @readonly($nicknameLockedUntil)
        />
      </label>
      @error('nickname', 'avatar')
        <p class="profile-panel__error" role="alert">{{ $message }}</p>
      @enderror

      <button class="avatar-modal__save js-avatar-save" type="submit">{{ __('profile.save') }}</button>
    </form>
  </div>
@endsection
