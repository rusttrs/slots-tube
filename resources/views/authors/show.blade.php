@extends('layouts.app')

@php
  $locale = app()->getLocale();
  $name = $author->displayName();
  $pageTitle = $author->pageTitle();
  $position = $author->positionLabel();
  $tags = $author->tags();
  $bio = $author->renderedBio();
  $avatar = $author->avatarUrl();
  $teamUrl = rtrim(localized_url($locale, 'authors'), '/').'/';
  $hasFavorites = $favoriteSlots->isNotEmpty() || $redFlagSlots->isNotEmpty() || $topStreamers !== [] || $favoritePosts->isNotEmpty();
  $slotUrl = fn ($slot) => rtrim($slot->publicUrl(), '/').'/';
  $slotLinks = fn ($slots) => $slots->map(fn ($slot) => '<a href="'.e($slotUrl($slot)).'">'.e($slot->displayTitle()).'</a>')->implode(', ');
  $streamerLinks = collect($topStreamers)->map(fn (array $streamer) => $streamer['url']
    ? '<a href="'.e($streamer['url']).'" target="_blank" rel="noopener nofollow">'.e($streamer['name']).'</a>'
    : e($streamer['name']))->implode(', ');
@endphp

@section('title', $author->metaTitle())
@section('meta_description', $author->metaDescription())

@push('head')
  <link rel="canonical" href="{{ $canonical }}" />
  <meta property="og:type" content="profile" />
  <meta property="og:site_name" content="slots.tube" />
  <meta property="og:title" content="{{ $author->metaTitle() }}" />
  <meta property="og:description" content="{{ $author->metaDescription() }}" />
  <meta property="og:url" content="{{ $canonical }}" />
  @if($avatar)
    <meta property="og:image" content="{{ $avatar }}" />
  @endif
  <script type="application/ld+json">
    {!! json_encode([
      '@context' => 'https://schema.org',
      '@graph' => [
        array_filter([
          '@type' => 'ProfilePage',
          '@id' => $canonical.'#webpage',
          'url' => $canonical,
          'name' => $pageTitle,
          'inLanguage' => $locale,
          'breadcrumb' => ['@id' => $canonical.'#breadcrumb'],
          'mainEntity' => ['@id' => $canonical.'#person'],
        ]),
        array_filter([
          '@type' => 'Person',
          '@id' => $canonical.'#person',
          'name' => $name,
          'jobTitle' => $position,
          'image' => $avatar,
          'url' => $canonical,
          'worksFor' => ['@type' => 'Organization', 'name' => 'slots.tube', 'url' => url('/')],
        ]),
        [
          '@type' => 'BreadcrumbList',
          '@id' => $canonical.'#breadcrumb',
          'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('slot.home'), 'item' => localized_url($locale, '/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __('author.team'), 'item' => $teamUrl],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $pageTitle, 'item' => $canonical],
          ],
        ],
      ],
    ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
  </script>
@endpush

@section('content')
  <div class="main--author">
    <div class="container">
      <header class="page-intro page-intro--author">
        <nav class="breadcrumb" aria-label="{{ __('slot.breadcrumb') }}">
          <ol class="breadcrumb__list">
            <li class="breadcrumb__item">
              <a class="breadcrumb__link breadcrumb__link--home" href="{{ localized_url($locale, '/') }}">
                <img src="/assets/images/author/home.svg" alt="" width="24" height="24" />
                <span>{{ __('slot.home') }}</span>
              </a>
            </li>
            <li class="breadcrumb__item" aria-hidden="true"><img class="breadcrumb__sep" src="/assets/images/author/chevron.svg" alt="" width="16" height="16" /></li>
            <li class="breadcrumb__item"><a class="breadcrumb__link" href="{{ $teamUrl }}">{{ __('author.team') }}</a></li>
            <li class="breadcrumb__item" aria-hidden="true"><img class="breadcrumb__sep" src="/assets/images/author/chevron.svg" alt="" width="16" height="16" /></li>
            <li class="breadcrumb__item"><span class="breadcrumb__current" aria-current="page">{{ $pageTitle }}</span></li>
          </ol>
        </nav>
        <h1 class="page-intro__title page-intro__title--author">{{ $pageTitle }}</h1>
      </header>

      <section class="author-bio" aria-label="{{ __('author.profile') }}">
        <div class="author-bio__top">
          <div class="author-bio__card">
            <div class="author-bio__ava">
              <img class="author-bio__photo" src="{{ $avatar ?: asset('assets/images/avatar.png') }}" alt="{{ $name }}" width="254" height="254" />
              <div class="author-bio__brand">
                <img src="/assets/images/author/logo-white.svg" alt="" width="43" height="30" />
                <span>slots.tube</span>
              </div>
            </div>
          </div>
          <div class="author-bio__info">
            <h2 class="author-bio__name">{{ $name }}</h2>
            @if($position)
              <p class="author-bio__role">{{ $position }}</p>
            @endif
            @if($author->started_at)
              <div class="author-bio__joined">
                <p class="author-bio__joined-date"><time datetime="{{ $author->started_at->toDateString() }}">{{ $author->started_at->translatedFormat('F j, Y') }}</time></p>
                <p class="author-bio__joined-label">{{ __('author.joined') }}</p>
              </div>
            @endif
            @if($tags !== [])
              <ul class="author-bio__tags">
                @foreach($tags as $tag)
                  <li><span class="author-tag"><img src="/assets/images/author/tag-7.svg" alt="" width="24" height="17" />{{ $tag }}</span></li>
                @endforeach
              </ul>
            @endif
          </div>
          <img class="author-bio__wm author-bio__wm--lg" src="/assets/images/author/watermark-7.svg" alt="" width="258" height="180" aria-hidden="true" />
          <img class="author-bio__wm author-bio__wm--sm" src="/assets/images/author/watermark-7-sm.svg" alt="" width="68" height="47" aria-hidden="true" />
        </div>
        @if($bio !== '')
          <div class="author-bio__text">{!! $bio !!}</div>
        @endif
      </section>

      @if($hasFavorites)
        <section class="author-panel" aria-labelledby="author-fav-title">
          <h2 class="author-panel__title" id="author-fav-title">{{ __('author.favorites_title') }}</h2>
          <div class="author-fav">
            @if($favoriteSlots->isNotEmpty())
              <div class="author-fav__block">
                <span class="author-tag"><img src="/assets/images/author/icon-heart.svg" alt="" width="16" height="16" />{{ __('author.favorite_slots') }}</span>
                <p class="author-fav__links">{!! $slotLinks($favoriteSlots) !!}</p>
              </div>
            @endif
            @if($redFlagSlots->isNotEmpty())
              <div class="author-fav__block">
                <span class="author-tag"><img src="/assets/images/author/icon-x.svg" alt="" width="15" height="15" />{{ __('author.red_flag_slots') }}</span>
                <p class="author-fav__links">{!! $slotLinks($redFlagSlots) !!}</p>
              </div>
            @endif
            @if($topStreamers !== [])
              <div class="author-fav__block">
                <span class="author-tag"><img src="/assets/images/author/icon-gamepad.svg" alt="" width="16" height="16" />{{ __('author.top_streamers') }}</span>
                <p class="author-fav__links">{!! $streamerLinks !!}</p>
              </div>
            @endif
            @if($favoritePosts->isNotEmpty())
              <div class="author-fav__block author-fav__block--posts">
                <span class="author-tag"><img src="/assets/images/author/icon-news.svg" alt="" width="16" height="16" />{{ __('author.favorite_posts') }}</span>
                <ul class="author-fav__posts">
                  @foreach($favoritePosts as $favoritePost)
                    <li><img src="/assets/images/author/{{ $loop->first ? 'icon-star.svg' : 'icon-star-2.svg' }}" alt="" width="16" height="16" /><a href="{{ rtrim($favoritePost->publicUrl(), '/').'/' }}">{{ $favoritePost->displayTitle() }}</a></li>
                  @endforeach
                </ul>
              </div>
            @endif
          </div>
        </section>
      @endif

      @if($slots->isNotEmpty())
        <section class="author-panel" aria-labelledby="author-slots-title">
          <h2 class="author-panel__title" id="author-slots-title">{{ __('author.latest_slots', ['name' => $name]) }}</h2>
          <div class="author-slots">
            @foreach($slots as $slot)
              <a class="slot-card author-slot" href="{{ $slotUrl($slot) }}">
                <span class="slot-card__media">
                  <img class="slot-card__cover" src="{{ $slot->aboutCoverUrl() ?: asset('assets/images/slot-cover.png') }}" alt="{{ $slot->displayTitle() }}" width="172" height="224" loading="lazy" />
                  <img class="author-slot__logo" src="/assets/images/author/slot-mini-logo.svg" alt="" width="131" height="24" />
                  @if($slot->rtpPercentLabel())
                    <span class="slot-card__rtp">RTP {{ $slot->rtpPercentLabel() }}</span>
                  @endif
                </span>
                <span class="slot-card__title">{{ $slot->displayTitle() }}</span>
              </a>
            @endforeach
          </div>
          <a class="author-cta" href="{{ rtrim(localized_url($locale, 'free-slots'), '/').'/' }}">{{ __('author.explore_slots') }}</a>
        </section>
      @endif

      @if($posts->isNotEmpty())
        <section class="author-panel" aria-labelledby="author-news-title">
          <h2 class="author-panel__title" id="author-news-title">{{ __('author.latest_posts', ['name' => $name]) }}</h2>
          <div class="author-news">
            @foreach($posts as $post)
              @include('content.partials.card', ['post' => $post])
            @endforeach
          </div>
          <a class="author-cta" href="{{ rtrim(localized_url($locale, 'content'), '/').'/' }}">{{ __('content.explore_news') }}</a>
        </section>
      @endif
    </div>
  </div>
@endsection
