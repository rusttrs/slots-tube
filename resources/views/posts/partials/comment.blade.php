@php
  $author = $comment->user;
  $name = $author?->displayName() ?: 'slots.tube';
  $when = $comment->created_at?->diffForHumans(['skip' => ['week']]);
@endphp

<div class="topic-comment" id="comment-{{ $comment->id }}">
  <div class="topic-comment__head">
    <div class="topic-comment__who">
      <span class="topic__avatar">
        <img src="{{ $author?->avatarUrl() ?: asset('assets/images/header/profile.svg') }}" alt="" />
      </span>
      <span class="topic-comment__meta">
        <span class="topic-comment__name">{{ $name }}</span>
        @if($when)
          <span class="topic-comment__date">{{ $when }}</span>
        @endif
      </span>
    </div>
    @if(auth()->check() && (int) auth()->id() === (int) $comment->user_id)
      <details class="topic-comment__more">
        <summary aria-label="{{ __('post.more') }}">
          <svg class="topic-icon topic-icon--30" viewBox="0 0 30 30" width="30" height="30" aria-hidden="true">
            <rect x="4" y="4" width="22" height="22" rx="4" fill="none" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="10.5" cy="15" r="1.3" fill="currentColor"/>
            <circle cx="15" cy="15" r="1.3" fill="currentColor"/>
            <circle cx="19.5" cy="15" r="1.3" fill="currentColor"/>
          </svg>
        </summary>
        <form method="post" action="{{ route('posts.comments.destroy', $comment) }}">
          @csrf
          @method('DELETE')
          <button type="submit">{{ __('post.delete') }}</button>
        </form>
      </details>
    @else
      <span class="topic-comment__more topic-comment__more--static" aria-hidden="true">
        <svg class="topic-icon topic-icon--30" viewBox="0 0 30 30" width="30" height="30">
          <rect x="4" y="4" width="22" height="22" rx="4" fill="none" stroke="currentColor" stroke-width="1.6"/>
          <circle cx="10.5" cy="15" r="1.3" fill="currentColor"/>
          <circle cx="15" cy="15" r="1.3" fill="currentColor"/>
          <circle cx="19.5" cy="15" r="1.3" fill="currentColor"/>
        </svg>
      </span>
    @endif
  </div>
  @if(filled($comment->body))
    <p class="topic-comment__text">{{ $comment->body }}</p>
  @endif
  @if($comment->imageUrl())
    <img class="topic-comment__photo" src="{{ $comment->imageUrl() }}" alt="" />
  @endif
  <div class="topic-comment__bar">
    <div class="topic-comment__actions">
      @auth
        <button
          class="topic-comment__link js-like-comment {{ $comment->liked_by_me ? 'is-liked' : '' }}"
          type="button"
          data-like-url="{{ route('posts.comments.like', $comment) }}"
        >{{ __('post.like') }}</button>
      @else
        <button class="topic-comment__link js-open-auth" type="button" data-auth-return="here">{{ __('post.like') }}</button>
      @endauth
      <button
        class="topic-comment__link js-reply"
        type="button"
        data-parent="{{ $comment->id }}"
        data-mention="{{ $name }}"
      >{{ __('post.reply') }}</button>
    </div>
    <span class="topic-comment__likes js-likes-label" data-count="{{ (int) $comment->likes_count }}">{{ trans_choice('post.likes', (int) $comment->likes_count, ['count' => (int) $comment->likes_count]) }}</span>
  </div>
</div>
