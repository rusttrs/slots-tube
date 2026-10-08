@extends('layouts.app')

@php
  $postTitle = $post->displayTitle();
  $excerpt = trim(strip_tags($post->displayExcerpt()));
  $authorName = $post->author?->displayName() ?: 'slots.tube';
  $authorAvatar = $post->author?->avatarUrl();
  $publishedAt = $post->created_at;
  $showUpdate = $post->showsContentUpdate();
  $updatedAt = $showUpdate ? $post->content_updated_on : null;
  $likesCount = (int) $post->likes_count;
  $likedName = $likeSamples->first()?->user?->displayName();
  $likedLabel = '';
  if ($likesCount === 1 && filled($likedName)) {
      $likedLabel = __('post.liked_by', ['name' => $likedName]);
  } elseif ($likesCount > 1 && filled($likedName)) {
      $likedLabel = trans_choice('post.liked_by_others', $likesCount - 1, [
          'name' => $likedName,
          'count' => $likesCount - 1,
      ]);
  }
  $backLabel = $post->backLabel();
  $openParent = (string) old('parent_id');
  $viewer = $viewer ?? auth()->user();
@endphp

@section('title', $postTitle.' | slots.tube')
@section('meta_description', $excerpt !== '' ? $excerpt : $postTitle)
@section('body_class', 'page-topic')

@push('head')
  <link rel="canonical" href="{{ $canonical }}" />
  <meta property="og:title" content="{{ $postTitle }}" />
  <meta property="og:url" content="{{ $canonical }}" />
  <meta property="og:type" content="article" />
  @if($excerpt !== '')
    <meta property="og:description" content="{{ $excerpt }}" />
  @endif
  @if($post->coverUrl())
    <meta property="og:image" content="{{ $post->coverUrl() }}" />
  @endif
@endpush

@section('content')
  <div class="topic-page" data-liked-template data-liked-one="{{ __('post.liked_by', ['name' => ':name']) }}" data-liked-many="{{ trans_choice('post.liked_by_others', 2, ['name' => ':name', 'count' => ':count']) }}" data-enlarge-label="{{ __('post.enlarge_image') }}">
    <div class="topic-back">
      <a class="topic-back__link" href="{{ $post->sectionUrl() }}">
        <x-site-icon name="topic-back" class="topic-icon topic-icon--20" width="20" height="20" />
        <span>{{ $backLabel }}</span>
      </a>
      <span class="topic-back__line"></span>
    </div>

    <article class="topic">
      @if($post->coverUrl())
        <img class="topic__hero" src="{{ $post->coverUrl() }}" alt="{{ $postTitle }}" />
      @endif

      <div class="topic__pad">
        <div class="topic__intro">
          <h1 class="topic__title">{{ $postTitle }}</h1>
          <div class="topic__byline">
            <span class="topic__avatar">
              @if($authorAvatar)
                <img src="{{ $authorAvatar }}" alt="" />
              @else
                <span class="topic__avatar-fallback">{{ mb_strtoupper(mb_substr($authorName, 0, 1)) }}</span>
              @endif
            </span>
            <span class="topic__byline-text">
              @if($post->author?->is_published)
                <a class="topic__author" href="{{ rtrim($post->author->publicUrl(), '/').'/' }}">{{ $authorName }}</a>
              @else
                <span class="topic__author">{{ $authorName }}</span>
              @endif
              @if($publishedAt)
                <time class="topic__date" datetime="{{ $publishedAt->toAtomString() }}">{{ $publishedAt->diffForHumans(['skip' => ['week']]) }}</time>
              @endif
              @if($showUpdate && $updatedAt)
                <span class="topic__updated">
                  <span class="topic__updated-label">{{ __('post.updated') }}</span>
                  <time datetime="{{ $updatedAt->toDateString() }}">{{ $updatedAt->translatedFormat('F j, Y') }}</time>
                </span>
              @endif
            </span>
          </div>
        </div>

        <div class="topic__content">
          {!! $post->renderedBody() !!}
        </div>

        <div class="topic__liked" data-liked-row @if($likesCount < 1) hidden @endif>
          <span class="topic__faces">
            @foreach($likeSamples as $like)
              <span class="topic__face">
                <img src="{{ $like->user?->avatarUrl() ?: asset('assets/images/header/profile.svg') }}" alt="" />
              </span>
            @endforeach
          </span>
          <x-site-icon name="topic-heart" class="topic-icon topic-icon--20" width="20" height="20" />
          <span class="topic__liked-label" data-liked-label>{{ $likedLabel }}</span>
        </div>
      </div>

      <hr class="topic__rule" />

      <div class="topic__actions">
        <div class="topic__action-group">
          @auth
            <button
              class="topic__action js-like-post {{ $post->liked_by_me ? 'is-liked' : '' }}"
              type="button"
              data-like-url="{{ route('posts.like', $post) }}"
            >
              <x-site-icon name="topic-heart" class="topic-icon topic-icon--20 topic-heart" width="20" height="20" />
              <span>{{ __('post.like') }}</span>
            </button>
          @else
            <button class="topic__action js-open-auth" type="button" data-auth-return="here">
              <x-site-icon name="topic-heart" class="topic-icon topic-icon--20" width="20" height="20" />
              <span>{{ __('post.like') }}</span>
            </button>
          @endauth
          <button class="topic__action" type="button" data-scroll-composer>
            <x-site-icon name="topic-comment" class="topic-icon topic-icon--20" width="20" height="20" />
            <span>{{ __('post.comment') }}</span>
          </button>
        </div>
        <span class="topic__comments-count">{{ trans_choice('post.comments', (int) $post->published_comments_count, ['count' => (int) $post->published_comments_count]) }}</span>
      </div>

      <hr class="topic__rule" />

      <div class="topic-comments" id="post-comments" data-translate-url="{{ route('translate') }}">
        @if($errors->any())
          <p class="topic-form-error" role="alert">{{ $errors->first() }}</p>
        @endif

        @foreach($roots as $root)
          @php
            $replies = $threads->get($root->id, collect());
            $threadOpen = $openParent !== '' && ($openParent === (string) $root->id || $replies->contains(fn ($reply) => (string) $reply->id === $openParent));
            $replyParent = $threadOpen ? $openParent : $root->id;
            $replyMention = ($replies->last() ?? $root)->user?->displayName() ?: 'slots.tube';
            if ($threadOpen) {
                $mentioned = $replies->first(fn ($reply) => (string) $reply->id === $openParent);
                if ($mentioned) {
                    $replyMention = $mentioned->user?->displayName() ?: $replyMention;
                }
            }
          @endphp
          <div class="topic-comment-block">
            @include('posts.partials.comment', ['comment' => $root, 'post' => $post])
            <div class="topic-thread {{ $replies->isEmpty() ? 'is-empty' : '' }} {{ $threadOpen ? 'is-open' : '' }}">
              @foreach($replies as $reply)
                @include('posts.partials.comment', ['comment' => $reply, 'post' => $post])
              @endforeach
              @include('posts.partials.composer', [
                'post' => $post,
                'viewer' => $viewer,
                'parentId' => $replyParent,
                'mention' => $replyMention,
                'isReply' => true,
                'composerId' => 'reply-'.$root->id,
                'value' => $threadOpen ? old('body') : '',
              ])
            </div>
          </div>
        @endforeach
      </div>

      <hr class="topic__rule" />

      @include('posts.partials.composer', [
        'post' => $post,
        'viewer' => $viewer,
        'parentId' => null,
        'isReply' => false,
        'composerId' => 'post-composer',
        'value' => $openParent === '' ? old('body') : '',
      ])
    </article>
  </div>

  <div class="image-lightbox" id="image-lightbox" hidden role="dialog" aria-modal="true" aria-label="{{ __('post.enlarged_image') }}">
    <div class="image-lightbox__backdrop js-close-lightbox" tabindex="-1"></div>
    <button type="button" class="image-lightbox__nav image-lightbox__nav--prev" id="image-lightbox-prev" aria-label="{{ __('post.previous_image') }}">‹</button>
    <button type="button" class="image-lightbox__close js-close-lightbox" aria-label="{{ __('post.close') }}">×</button>
    <button type="button" class="image-lightbox__nav image-lightbox__nav--next" id="image-lightbox-next" aria-label="{{ __('post.next_image') }}">›</button>
    <img class="image-lightbox__img" id="image-lightbox-img" src="" alt="" />
  </div>
@endsection

@push('scripts')
  <script src="/js/ugc-translate.js?v=20261008-tr1" defer></script>
  <script src="/js/post-page.js?v=20261008-tr1" defer></script>
@endpush
