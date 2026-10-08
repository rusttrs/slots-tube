@php
  $viewer = $viewer ?? auth()->user();
  $parentId = $parentId ?? null;
  $mention = $mention ?? null;
  $value = $value ?? '';
  $composerId = $composerId ?? 'post-composer';
  $isReply = (bool) ($isReply ?? filled($parentId));
@endphp

<form
  class="topic-composer {{ $isReply ? 'is-reply' : '' }}"
  id="{{ $composerId }}"
  method="post"
  action="{{ route('posts.comments.store', $post) }}"
  enctype="multipart/form-data"
>
  @csrf
  @if($parentId)
    <input type="hidden" name="parent_id" value="{{ $parentId }}" />
  @endif
  <span class="topic__avatar topic__avatar--composer">
    @if($viewer)
      <img src="{{ $viewer->avatarUrl() }}" alt="" />
    @else
      <img src="{{ asset('assets/images/header/profile.svg') }}" alt="" />
    @endif
  </span>
  <div class="topic-composer__box">
    <div class="topic-composer__field">
      @if($isReply)
        <span class="topic-composer__mention">{{ $mention }}</span>
      @endif
      <textarea
        name="body"
        rows="1"
        maxlength="2000"
        placeholder="{{ $isReply ? '' : __('post.thoughts') }}"
        @guest aria-disabled="true" @endguest
      >{{ $value }}</textarea>
    </div>
    <div class="topic-composer__bar">
      @auth
        <button class="topic-composer__attach js-attach-image" type="button" aria-label="{{ __('post.attach') }}">
          <svg class="topic-icon topic-icon--30" viewBox="0 0 30 30" width="30" height="30" aria-hidden="true">
            <rect x="4.5" y="6.5" width="21" height="17" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="11" cy="12.5" r="1.6" fill="currentColor"/>
            <path d="M6.5 20.5 12 15l4 3.5 3.2-2.6 4.8 4.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
          </svg>
        </button>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" hidden />
      @else
        <button class="topic-composer__attach js-open-auth" type="button" data-auth-return="here" aria-label="{{ __('post.attach') }}">
          <svg class="topic-icon topic-icon--30" viewBox="0 0 30 30" width="30" height="30" aria-hidden="true">
            <rect x="4.5" y="6.5" width="21" height="17" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="11" cy="12.5" r="1.6" fill="currentColor"/>
            <path d="M6.5 20.5 12 15l4 3.5 3.2-2.6 4.8 4.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
          </svg>
        </button>
      @endauth
      <span class="topic-composer__file js-attach-name" hidden></span>
      @auth
        <button class="topic-composer__submit" type="submit">{{ __('post.post') }}</button>
      @else
        <button class="topic-composer__submit js-open-auth" type="button" data-auth-return="here">{{ __('post.post') }}</button>
      @endauth
    </div>
  </div>
</form>
