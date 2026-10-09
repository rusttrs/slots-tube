@extends('layouts.app')

@php
  $name = $user->displayName();
  $metaTitle = __('profile.public_meta_title', ['name' => $name]);
  $metaDescription = __('profile.public_meta_description', ['name' => $name]);
  $withSlash = fn (string $url) => rtrim($url, '/').'/';
@endphp

@section('title', $metaTitle)
@section('meta_description', $metaDescription)
@section('robots', 'noindex, follow')

@push('head')
  <link rel="canonical" href="{{ $canonical }}" />
  <meta property="og:type" content="profile" />
  <meta property="og:site_name" content="slots.tube" />
  <meta property="og:title" content="{{ $metaTitle }}" />
  <meta property="og:description" content="{{ $metaDescription }}" />
  <meta property="og:url" content="{{ $canonical }}" />
@endpush

@section('content')
  <div class="container">
    <div class="profile-layout">
      @include('users.partials.side', [
        'user' => $user,
        'active' => $isOwn ? 'activity' : null,
        'nameTag' => 'h1',
        'subtitle' => __('profile.joined', ['date' => $user->created_at?->translatedFormat('F Y')]),
        'stats' => [
          __('profile.reviews_count', ['count' => $reviewsCount]),
          __('profile.comments_count', ['count' => $commentsCount]),
        ],
      ])

      <div class="profile-main">
        <section class="profile-panel" id="reviews">
          <div class="profile-panel__head">
            <h2 class="profile-panel__title">{{ __('profile.reviews_title') }}</h2>
            <span class="profile-panel__count">{{ $reviewsCount }}</span>
          </div>
          @forelse($reviews as $review)
            @if($loop->first)<ul class="profile-activity">@endif
            <li class="profile-activity__item">
              <div class="profile-activity__head">
                <a class="profile-activity__title" href="{{ $withSlash($review->slot->publicUrl()) }}#player-reviews">{{ $review->slot->displayTitle() }}</a>
                <b class="profile-activity__rating">{{ number_format((float) $review->rating, 1) }}</b>
              </div>
              <p class="profile-activity__meta">
                {{ $review->play_mode === 'real' ? __('slot.real_money') : __('slot.demo_play') }}{{ $review->played_myself ? __('slot.verified') : '' }}
                · <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->translatedFormat('F j, Y') }}</time>
              </p>
              @if(filled($review->body))
                <p class="profile-activity__text">{{ $review->body }}</p>
              @endif
            </li>
            @if($loop->last)</ul>@endif
          @empty
            <p class="profile-panel__hint">{{ __('profile.no_reviews') }}</p>
          @endforelse
          @if($reviewsCount > $reviews->count())
            <p class="profile-panel__hint">{{ __('profile.latest_only', ['count' => $reviews->count()]) }}</p>
          @endif
        </section>

        <section class="profile-panel" id="comments">
          <div class="profile-panel__head">
            <h2 class="profile-panel__title">{{ __('profile.comments_title') }}</h2>
            <span class="profile-panel__count">{{ $commentsCount }}</span>
          </div>
          @forelse($comments as $comment)
            @if($loop->first)<ul class="profile-activity">@endif
            <li class="profile-activity__item">
              <div class="profile-activity__head">
                <a class="profile-activity__title" href="{{ $withSlash($comment->post->publicUrl()) }}#comment-{{ $comment->id }}">{{ $comment->post->displayTitle() }}</a>
              </div>
              <p class="profile-activity__meta">
                <time datetime="{{ $comment->created_at->toDateString() }}">{{ $comment->created_at->translatedFormat('F j, Y') }}</time>
              </p>
              @if(filled($comment->body))
                <p class="profile-activity__text">{{ $comment->body }}</p>
              @endif
              @if($comment->imageUrl())
                <img class="profile-activity__photo" src="{{ $comment->imageUrl() }}" alt="{{ __('profile.image_comment') }}" loading="lazy" />
              @endif
            </li>
            @if($loop->last)</ul>@endif
          @empty
            <p class="profile-panel__hint">{{ __('profile.no_comments') }}</p>
          @endforelse
          @if($commentsCount > $comments->count())
            <p class="profile-panel__hint">{{ __('profile.latest_only', ['count' => $comments->count()]) }}</p>
          @endif
        </section>
      </div>
    </div>
  </div>
@endsection
