@php
  $title = $slot->displayTitle();
  $h1 = $slot->h1Title();
  $metaTitle = $slot->getTranslation('meta_title', app()->getLocale())
    ?: $slot->getTranslation('meta_title', 'en')
    ?: ($h1.' | SlotsTube');
  $metaDesc = $slot->getTranslation('meta_description', app()->getLocale())
    ?: $slot->getTranslation('meta_description', 'en')
    ?: $slot->subtitle();
  $avg = $slot->averageRating();
  $reviewsCount = $slot->reviewsCount();
  $editorial = $slot->editorial_score;
  $providerName = $slot->provider?->displayName() ?: 'Provider';
  $authorName = $slot->author?->displayName();
  $publisherName = $authorName;
  $publishedAt = $slot->created_at;
  $showUpdate = $slot->showsContentUpdate();
  $updatedAt = $showUpdate ? $slot->content_updated_on : null;
  $editorName = $showUpdate ? $slot->updatedByAuthor?->displayName() : null;
  $cover = $slot->coverUrl();
  $pros = implode(', ', lines_to_array($slot->pros));
  $cons = implode(', ', lines_to_array($slot->cons));
  $bestFor = lines_to_array($slot->best_for);
  $notIdeal = lines_to_array($slot->not_ideal_for);
  $mobileChecklist = lines_to_array($slot->mobile_checklist);
  $symbols = $slot->symbolCards();
  $paytable = $slot->paytableView();
  $paytableRows = $paytable['rows'];
  $faq = is_array($slot->faq) ? $slot->faq : [];
  $screenshots = $slot->screenshotGallery();
  $mobileShots = $slot->mobileScreenshotGallery();
  $interpretCards = $slot->interpretGuideCards();
  $rtpIntro = trim(html_entity_decode(strip_tags((string) $slot->rtp_section_text)));
  $responsibleIntro = trim(html_entity_decode(strip_tags((string) $slot->responsible_play)));
  $responsibleSteps = $slot->responsibleSteps();
  $authUser = auth()->user();
  $locale = app()->getLocale();
  $site = rtrim(config('app.url'), '/');
  $gameInfoIcons = [
    'provider' => 'info-provider',
    'release_date' => 'info-release-date',
    'game_type' => 'info-game-type',
    'grid' => 'info-reels',
    'win_system' => 'info-playlines',
    'rtp' => 'info-rtp',
    'max_win' => 'info-max-win',
    'volatility' => 'info-volatility',
    'stake_range' => 'info-min-max-bet',
    'features' => 'info-features',
    'theme' => 'info-theme',
    'wild' => 'info-wild',
    'free_spins' => 'info-free-spins',
    'progressive' => 'info-progressive',
    'bonus_buy' => 'info-bonus-buy',
    'tumbling' => 'info-quickspin',
    'gamble' => 'info-gamble',
    'scatter' => 'info-scatter',
    'technology' => 'info-technology',
  ];
  $stars = function (?float $score, int $max = 5) {
      $filled = $score !== null ? (int) round($score) : 0;
      return max(0, min($max, $filled));
  };
  $rtpHigh = $slot->rtp_max !== null ? number_format((float) $slot->rtp_max, 2).'%' : ($slot->rtp !== null ? number_format((float) $slot->rtp, 2).'%' : null);
  $rtpLow = $slot->rtp_min !== null ? number_format((float) $slot->rtp_min, 2).'%' : $rtpHigh;
  $volKey = strtolower(trim((string) $slot->volatility));
  $volBars = 3;
  if ($volKey === '') {
      $volBars = 0;
  } elseif (str_contains($volKey, 'very') || str_contains($volKey, 'extreme')) {
      $volBars = 5;
  } elseif (str_contains($volKey, 'high')) {
      $volBars = 4;
  } elseif (str_contains($volKey, 'low')) {
      $volBars = 2;
  }
  $showRtp = $rtpIntro !== '' || $interpretCards !== [] || $rtpHigh || filled($slot->volatility) || filled($slot->max_win);
  $reviewLayout = \App\Models\Slot::layoutPlayerReviews($reviews);
  $reviewCards = $reviewLayout['cards'];
  $visibleReviewIds = $reviewLayout['visibleIds'];
  $wideReviewIds = $reviewLayout['wideIds'];
  $hiddenReviewCount = $reviewCards->reject(fn ($review) => in_array($review->id, $visibleReviewIds, true))->count();
  $toc = [];
  $addToc = function (string $href, string $label, string $icon) use (&$toc): void {
      $toc[] = ['href' => $href, 'label' => $label, 'icon' => $icon];
  };
  if (filled($slot->review_overview) || $pros || $cons) {
      $addToc('#review-guide', __('slot.toc_review_overview'), 'toc-review-guide');
  }
  if (filled($slot->audience_intro) || $bestFor || $notIdeal) {
      $addToc('#audience-fit', __('slot.toc_audience_fit'), 'toc-review-guide');
  }
  if ($gameInfo->isNotEmpty()) {
      $addToc('#game-info', __('slot.toc_game_information'), 'toc-game-info');
  }
  if ($slot->bonuses->isNotEmpty()) {
      $addToc('#operator-offers', __('slot.toc_where_to_play'), 'toc-bonus-features');
  }
  if ($slot->show_session_data) {
      $addToc('#reality-network', __('slot.toc_observed'), 'toc-slot-rtp');
  }
  if (filled($slot->bonus_features_body)) {
      $addToc('#bonus-features', __('slot.toc_features'), 'toc-bonus-features');
  }
  if (filled($slot->experience_title) || filled($slot->experience_body)) {
      $addToc('#demo-check', __('slot.toc_experience'), 'toc-free-spins');
  }
  if (filled($responsibleIntro) || $responsibleSteps !== []) {
      $addToc('#how-to-play', __('slot.toc_how_to_play'), 'toc-how-to-play');
  }
  if (filled($slot->symbols_intro) || filled($slot->symbols_mid) || filled($slot->symbols_outro) || $symbols !== [] || $paytableRows !== []) {
      $addToc('#symbols', __('slot.toc_paytable'), 'toc-symbols');
  }
  if ($showRtp) {
      $addToc('#slot-rtp', __('slot.toc_rtp'), 'toc-slot-rtp');
  }
  if ($screenshots) {
      $addToc('#screenshots', __('slot.toc_screenshots'), 'toc-screenshots');
  }
  if ($mobileShots || filled($slot->mobile_intro) || $mobileChecklist) {
      $addToc('#mobile', __('slot.toc_mobile'), 'toc-screenshots');
  }
  $addToc('#similar-slots', __('slot.toc_similar'), 'toc-review');
  if ($slot->showsMethodology()) {
      $addToc('#methodology', __('slot.toc_methodology'), 'toc-review-guide');
  }
  $addToc('#player-reviews', __('slot.toc_player_reviews'), 'toc-review-guide');
  if ($faq) {
      $addToc('#faq', __('slot.toc_faq'), 'toc-faq');
  }
@endphp

@extends('layouts.app')

@section('title', $metaTitle)
@section('meta_description', $metaDesc)
@section('body_class', 'page-slot')

@push('head')
  <link rel="canonical" href="{{ $canonical }}" />
  <meta property="og:type" content="article" />
  <meta property="og:site_name" content="SlotsTube" />
  <meta property="og:title" content="{{ $h1 }}" />
  <meta property="og:description" content="{{ $metaDesc }}" />
  <meta property="og:url" content="{{ $canonical }}" />
  @if($cover)
    <meta property="og:image" content="{{ $cover }}" />
  @endif
  @if($publishedAt)
    <meta property="article:published_time" content="{{ $publishedAt->toIso8601String() }}" />
  @endif
  @if($updatedAt)
    <meta property="article:modified_time" content="{{ $updatedAt->toIso8601String() }}" />
  @endif
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{{ $h1 }}" />
  <meta name="twitter:description" content="{{ $metaDesc }}" />
  @if($cover)
    <meta name="twitter:image" content="{{ $cover }}" />
  @endif
  <script type="application/ld+json">
    {!! json_encode([
      '@@context' => 'https://schema.org',
      '@graph' => [
        [
          '@type' => 'Organization',
          '@id' => $site.'/#organization',
          'name' => 'SlotsTube',
          'url' => $site.'/',
          'logo' => $site.'/assets/icons/logo-7.svg',
        ],
        [
          '@type' => 'WebPage',
          '@id' => $canonical.'#webpage',
          'url' => $canonical,
          'name' => $h1,
          'description' => $metaDesc,
          'inLanguage' => $locale,
          'datePublished' => optional($publishedAt)->toDateString(),
          'dateModified' => optional($updatedAt ?: $publishedAt)->toDateString(),
          'breadcrumb' => ['@id' => $canonical.'#breadcrumb'],
          'mainEntity' => ['@id' => $canonical.'#game'],
        ],
        array_filter([
          '@type' => 'Article',
          '@id' => $canonical.'#article',
          'headline' => $h1,
          'description' => $metaDesc,
          'inLanguage' => $locale,
          'datePublished' => optional($publishedAt)->toDateString(),
          'dateModified' => optional($updatedAt ?: $publishedAt)->toDateString(),
          'author' => $publisherName
            ? ['@type' => 'Person', 'name' => $publisherName]
            : ['@id' => $site.'/#organization'],
          'editor' => $editorName
            ? ['@type' => 'Person', 'name' => $editorName]
            : null,
          'publisher' => ['@id' => $site.'/#organization'],
          'mainEntityOfPage' => ['@id' => $canonical.'#webpage'],
          'about' => ['@id' => $canonical.'#game'],
          'image' => $cover,
        ], fn ($value) => $value !== null),
        [
          '@type' => 'BreadcrumbList',
          '@id' => $canonical.'#breadcrumb',
          'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => localized_url($locale, '/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Slots', 'item' => localized_url($locale, 'free-slots')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $canonical],
          ],
        ],
        array_filter([
          '@type' => 'VideoGame',
          '@id' => $canonical.'#game',
          'name' => $title,
          'genre' => $slot->game_type ?: 'Video slot',
          'datePublished' => optional($slot->release_date)->format('Y-m'),
          'creator' => $slot->provider ? ['@type' => 'Organization', 'name' => $providerName] : null,
          'aggregateRating' => ($avg && $reviewsCount)
            ? [
              '@type' => 'AggregateRating',
              'ratingValue' => $avg,
              'ratingCount' => $reviewsCount,
              'bestRating' => 5,
              'worstRating' => 1,
            ]
            : null,
        ]),
      ],
    ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
  </script>
@endpush

@php
  $verdictLines = preg_split("/\n\s*\n/", trim(strip_tags((string) $slot->quick_verdict))) ?: [];
  $verdictTitle = trim($verdictLines[0] ?? '');
  $verdictText = trim(implode("\n\n", array_slice($verdictLines, 1)));
  $rtpLabel = $slot->rtpPercentLabel() ?: '—';
  $myReview = $authUser
    ? $slot->publishedReviews()->where('user_id', $authUser->id)->first()
    : null;
  $demoOffer = $slot->bonuses->first();
  $rtpFromRaw = $slot->rtp_min ?? $slot->rtp;
  $rtpToRaw = $slot->rtp_max ?? $slot->rtp;
  $rtpFrom = $rtpFromRaw !== null ? number_format((float) $rtpFromRaw, 2, '.', '').'%' : null;
  $rtpTo = $rtpToRaw !== null ? number_format((float) $rtpToRaw, 2, '.', '').'%' : null;
  $aboutCover = $slot->aboutCoverUrl();
  $providerUrl = ($slot->provider && $slot->provider->is_published)
    ? rtrim($slot->provider->publicUrl($locale), '/').'/'
    : '#similar-slots';
  $guidesUrl = rtrim(localized_url($locale ?? null, 'guides'), '/').'/';
@endphp

@section('content')
  <div class="container">
    <section class="page-intro" aria-label="{{ __('slot.page_intro') }}">
      <nav class="breadcrumb" aria-label="{{ __('slot.breadcrumb') }}">
        <ol class="breadcrumb__list">
          <li class="breadcrumb__item">
            <a class="breadcrumb__link breadcrumb__link--home" href="{{ localized_url($locale, '/') }}" aria-label="{{ __('slot.home') }}">
              <x-site-icon name="breadcrumb-home" width="24" height="24" />
              <span>{{ __('slot.home') }}</span>
            </a>
          </li>
          <li class="breadcrumb__item" aria-hidden="true">
            <x-site-icon name="chevron-right" class="breadcrumb__sep" width="16" height="16" />
          </li>
          <li class="breadcrumb__item">
            <a class="breadcrumb__link" href="{{ localized_url($locale, 'free-slots') }}">{{ __('slot.slots') }}</a>
          </li>
          <li class="breadcrumb__item" aria-hidden="true">
            <x-site-icon name="chevron-right" class="breadcrumb__sep" width="16" height="16" />
          </li>
          <li class="breadcrumb__item">
            <span class="breadcrumb__current" aria-current="page">{{ $title }}</span>
          </li>
        </ol>
      </nav>

      <h1 class="page-intro__title">
        <span class="page-intro__accent">{{ $title }}</span> {{ __('slot.h1_suffix') }}
      </h1>
      <p class="page-intro__subtitle">{{ $slot->subtitle() }}</p>
    </section>

    <section class="hero-play" id="demo" aria-label="{{ __('slot.demo_verdict') }}">
      <div class="hero-play__demo">
        @if($cover)
          <img
            class="hero-play__cover"
            src="{{ $cover }}"
            alt="{{ __('slot.demo_alt', ['title' => $title]) }}"
            width="780"
            height="460"
            fetchpriority="high"
            decoding="async"
          />
        @endif
        <div class="hero-play__overlay" aria-hidden="true"></div>
        <div class="hero-play__actions">
          @if($slot->demo_url)
            <button class="btn btn--play js-open-demo" type="button">{{ __('slot.play_for_free') }}</button>
          @else
            <button class="btn btn--play" type="button" disabled>{{ __('slot.demo_coming_soon') }}</button>
          @endif
          <p class="hero-play__legal">
            {{ __('slot.demo_legal') }}
          </p>
        </div>
      </div>

      <aside class="rank rank--verdict" aria-label="{{ __('slot.quick_verdict_aria') }}">
        <div class="rank__content">
          <div class="rank__player-score">
            <div class="rank__player-value">
              <strong id="slot-avg-rating">{{ $avg !== null ? number_format($avg, 1) : '—' }}</strong>
              <span class="rank__player-stars" aria-hidden="true">
                @for($i = 1; $i <= 5; $i++)
                  <x-site-icon :name="$i <= $stars($avg) ? 'star' : 'star-outline-orange'" width="16" height="16" />
                @endfor
              </span>
            </div>
            <div class="rank__player-copy">
              <span>{{ __('slot.reader_rating') }}</span>
              <small id="slot-reviews-count">{{ __('slot.based_on_player_reviews') }}</small>
            </div>
            <button
              class="rank__player-action {{ $authUser ? 'js-open-rank-modal' : 'js-open-auth' }}"
              type="button"
              aria-haspopup="dialog"
              @unless($authUser) data-after-login="rate" @endunless
            >{{ __('slot.rate') }}</button>
          </div>

          @if($verdictTitle || $verdictText)
            <div class="rank__title">
              <h2 class="rank__heading">{{ __('slot.quick_verdict') }}</h2>
            </div>
            @if($verdictTitle)
              <p class="rank__verdict-title">{{ $verdictTitle }}</p>
            @endif
            @if($verdictText)
              <p class="rank__verdict-text">{{ $verdictText }}</p>
            @endif
          @endif

          <dl class="rank__facts">
            <div class="rank__fact">
              <dt>{{ __('slot.best_for') }}</dt>
              <dd>{{ $bestFor[0] ?? '—' }}</dd>
            </div>
            <div class="rank__fact">
              <dt>{{ __('slot.risk_profile') }}</dt>
              <dd>{{ $slot->riskProfileLabel() }}</dd>
            </div>
            <div class="rank__fact">
              <dt>{{ __('slot.default_rtp') }}</dt>
              <dd>{{ $slot->rtpPercentLabel() ? $rtpLabel.__('slot.verify_live') : '—' }}</dd>
            </div>
          </dl>
        </div>
        <a class="rank__cta rank__cta--link" href="#review-guide">
          <span>{{ __('slot.read_independent_verdict') }}</span>
          <x-site-icon name="arrow-circle" width="12" height="6" />
        </a>
      </aside>
    </section>

    <section class="meta-published" aria-label="{{ __('slot.publication_info') }}">
      <p class="meta-published__item">
        <span class="meta-published__label">{{ __('slot.published') }}</span>
        <time class="meta-published__value" datetime="{{ optional($publishedAt)->toDateString() }}">{{ optional($publishedAt)->translatedFormat('F j, Y') }}</time>
        @if($publisherName)
          <span class="meta-published__value">{{ __('slot.by') }}</span>
          <span class="meta-published__value">{{ $publisherName }}</span>
        @endif
      </p>
      @if($showUpdate && $updatedAt)
        <span class="meta-published__divider" aria-hidden="true"></span>
        <p class="meta-published__item">
          <span class="meta-published__label">{{ __('slot.updated') }}</span>
          <time class="meta-published__value" datetime="{{ $updatedAt->toDateString() }}">{{ $updatedAt->translatedFormat('F j, Y') }}</time>
        </p>
      @endif
    </section>

    @if($slot->show_session_data)
      <aside class="observed-snapshot" aria-labelledby="observed-snapshot-title">
        <p class="observed-snapshot__text">
          <strong id="observed-snapshot-title">{{ __('slot.observed_snapshot_title') }}</strong><br />
          {!! __('slot.observed_snapshot_body', ['stats' => '<span id="observed-snapshot-stats">'.e(__('slot.observed_snapshot_stats')).'</span>']) !!}<br class="observed-snapshot__break" />
          <a class="observed-snapshot__link" href="#reality-network">{{ __('slot.view_full_observed') }}</a>.
        </p>
      </aside>
    @endif

    <section class="slot-about" aria-label="{{ __('slot.about_aria', ['title' => $title]) }}">
      <div class="slot-about__media">
        @if($aboutCover)
          <img class="slot-about__cover" src="{{ $aboutCover }}" alt="{{ $title }}" width="290" height="378" decoding="async" />
        @endif
        <div class="slot-about__shade" aria-hidden="true"></div>
        <div class="slot-about__brand">
          <img src="/assets/icons/logo-7-white.svg" alt="" width="43" height="30" />
          <span>slots.tube</span>
        </div>
      </div>
      <div class="slot-about__body">
        <h2 class="slot-about__title">{{ $title }}</h2>
        @if($editorial !== null)
          <div class="slot-about__rating" aria-label="{{ __('slot.editorial_score_aria', ['score' => number_format((float) $editorial, 1)]) }}">
            @for($i = 1; $i <= 5; $i++)
              <x-site-icon name="star-about" width="20" height="20" />
            @endfor
            <span>{{ __('slot.out_of_5', ['score' => number_format((float) $editorial, 1)]) }}</span>
          </div>
        @endif
        @if(filled($slot->about_text))
          <div class="slot-about__text">{!! $slot->about_text !!}</div>
        @endif
        <div class="slot-about__cta">
          <p class="slot-about__notice">
            {{ __('slot.about_notice') }}
          </p>
          <a class="btn btn--review" href="#bonus-features">{{ __('slot.read_full_review') }}</a>
        </div>
      </div>
    </section>

    <section class="desc-row" aria-label="{{ __('slot.rtp_and_provider') }}">
      <article class="rtp-card">
        <div class="rtp-card__intro">
          <h2 class="rtp-card__title">
            @if($rtpFrom && $rtpTo)
              {!! __('slot.slot_return_from_to', ['title' => $title, 'from' => $rtpFrom, 'to' => $rtpTo]) !!}
            @else
              {!! __('slot.slot_return', ['title' => $title, 'rtp' => $rtpLabel]) !!}
            @endif
          </h2>
          @if($slot->rtp_blurb)
            <p class="rtp-card__text">{{ $slot->rtp_blurb }}</p>
          @endif
        </div>
        <div class="rtp-card__guides">
          <a class="guide-link" href="{{ $guidesUrl }}">
            <span class="guide-link__text">
              <span class="guide-link__label">{{ __('slot.guide') }}</span>
              <span class="guide-link__title">{{ __('slot.what_is_rtp') }}</span>
            </span>
            <span class="guide-link__arrow" aria-hidden="true">
              <x-site-icon name="arrow-circle" width="12" height="6" />
            </span>
          </a>
          <a class="guide-link" href="#slot-rtp">
            <span class="guide-link__text">
              <span class="guide-link__label">{{ __('slot.guide') }}</span>
              <span class="guide-link__title">{{ __('slot.how_to_check_rtp', ['provider' => $providerName]) }}</span>
            </span>
            <span class="guide-link__arrow" aria-hidden="true">
              <x-site-icon name="arrow-circle" width="12" height="6" />
            </span>
          </a>
        </div>
      </article>

      @if($slot->provider)
        <aside class="provider-col" aria-label="{{ __('slot.provider_related') }}">
          <div class="provider-col__top">
            <a class="provider-logo" href="{{ $providerUrl }}">
              @if($slot->provider->logoUrl())
                <img src="{{ $slot->provider->logoUrl() }}" alt="{{ $providerName }}" width="340" height="172" />
              @else
                <span class="provider-logo__name">{{ $providerName }}</span>
              @endif
            </a>
            <div class="provider-card">
              <div class="provider-card__head">
                <div class="provider-card__tag">
                  <x-site-icon name="provider" width="20" height="20" />
                  <span>{{ __('slot.provider') }}</span>
                </div>
                <p class="provider-card__name">{{ $providerName }}</p>
              </div>
              <a class="provider-card__btn" href="{{ $providerUrl }}">{{ __('slot.all_games', ['count' => $providerGameCount]) }}</a>
            </div>
          </div>
          @if($providerSlots->isNotEmpty())
            <div class="related-games">
              @foreach($providerSlots->take(3) as $ps)
                <a class="related-game" href="{{ rtrim($ps->publicUrl(), '/').'/' }}">
                  @if($ps->aboutCoverUrl())
                    <img
                      src="{{ $ps->aboutCoverUrl() }}"
                      alt="{{ $ps->displayTitle() }}"
                      width="172"
                      height="224"
                      loading="lazy"
                    />
                  @endif
                  <span class="related-game__name">{{ $ps->displayTitle() }}</span>
                </a>
              @endforeach
            </div>
          @endif
        </aside>
      @endif
    </section>
  </div>

  <div class="container">
    <nav class="toc-mob" aria-label="{{ __('slot.article_sections') }}">
      @foreach($toc as $index => $item)
        <a class="toc-mob__link {{ $index === 0 ? 'toc-mob__link--active' : '' }}" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
      @endforeach
    </nav>

    <div class="article-layout">
      <div class="article-layout__main">
        @if($slot->review_overview || $pros || $cons)
          <section class="section" id="review-guide" data-toc-section>
            <h2 class="section__title">{{ __('slot.review_overview_title', ['title' => $title]) }}</h2>
            @if($slot->review_overview)
              <div class="section__text section__text--lg">{!! $slot->review_overview !!}</div>
            @endif
            @if($pros || $cons)
              <aside class="review-verdict" aria-label="{{ __('slot.pros_and_limitations') }}">
                <p class="review-verdict__label">{{ __('slot.pros_and_limitations') }}</p>
                <p class="review-verdict__text">
                  @if($pros)<strong>{{ __('slot.pros') }}</strong> {{ $pros }}@endif
                  @if($pros && $cons)<br />@endif
                  @if($cons)<strong>{{ __('slot.limitations') }}</strong> {{ $cons }}@endif
                </p>
              </aside>
            @endif
          </section>
        @endif

            @if($slot->audience_intro || $bestFor || $notIdeal)
          <section class="section" id="audience-fit" data-toc-section>
            <h2 class="section__title">{{ __('slot.who_suits', ['title' => $title]) }}</h2>
            @if($slot->audience_intro)
              <p class="section__text section__text--lg">{{ trim(strip_tags((string) $slot->audience_intro)) }}</p>
            @endif
            @if($bestFor || $notIdeal)
            <div class="review-split">
              @if($bestFor)
                <div class="review-panel review-panel--best">
                  <h3 class="review-panel__title">{{ __('slot.best_for') }}</h3>
                  <ul class="review-panel__list">
                    @foreach($bestFor as $item)
                      <li>
                        <x-site-icon name="check-green" class="review-panel__icon" width="16" height="12" />
                        <span>{{ $item }}</span>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif
              @if($notIdeal)
                <div class="review-panel review-panel--not">
                  <h3 class="review-panel__title">{{ __('slot.not_ideal_for') }}</h3>
                  <ul class="review-panel__list">
                    @foreach($notIdeal as $item)
                      <li>
                        <x-site-icon name="cross-red" class="review-panel__icon review-panel__icon--cross" width="14" height="14" />
                        <span>{{ $item }}</span>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif
            </div>
            @endif
          </section>
        @endif

        @if($gameInfo->isNotEmpty())
          <section class="slot-info" id="game-info" data-toc-section>
            <h2 class="slot-info__title">{{ __('slot.game_information_title', ['title' => $title]) }}</h2>
            <div class="slot-info__panel">
              <div class="slot-info__table" id="slot-info-table">
                @foreach($gameInfo as $label => $value)
                  @php $tallRow = in_array($label, ['features', 'theme'], true); @endphp
                  <div class="slot-info__row{{ $tallRow ? ' slot-info__row--tall' : '' }}">
                    <span class="slot-info__icon" aria-hidden="true">
                      <x-site-icon :name="$gameInfoIcons[$label] ?? 'info-features'" class="slot-info__glyph" width="28" height="28" />
                    </span>
                    <span class="slot-info__label">{{ __('slot.gi_'.$label) }}:</span>
                    <span class="slot-info__value">{{ $value }}</span>
                  </div>
                @endforeach
              </div>
              <div class="slot-info__fade" aria-hidden="true"></div>
            </div>
            <button
              class="slot-info__toggle js-slot-info-toggle"
              type="button"
              aria-expanded="false"
              aria-controls="slot-info-table"
            >
              <svg class="icon slot-info__toggle-icon" aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 20 20" fill="none">
                <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <span class="slot-info__toggle-label">{{ __('slot.show_all_game_details') }}</span>
              <svg class="icon slot-info__toggle-icon" aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 20 20" fill="none">
                <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
          </section>
        @endif

        @if($slot->bonuses->isNotEmpty())
          <section class="casino-offers casino-offers--inline" id="operator-offers" aria-labelledby="operator-offers-title">
            <p class="casino-offers__eyebrow">{{ __('slot.featured_casino_bonuses') }}</p>
            <p class="casino-offers__title" id="operator-offers-title">{{ __('slot.where_to_play', ['title' => $title]) }}</p>
            <p class="casino-offers__intro">
              {{ __('slot.where_to_play_intro') }}
            </p>
            <span class="casino-offers__line" aria-hidden="true"></span>
            <div class="casino-offers__carousel" aria-label="{{ __('slot.sponsored_casino_offers') }}">
              <div class="swiper" id="casino-offers-swiper">
                <div class="swiper-wrapper">
                  @foreach($slot->bonuses as $bonus)
                    <div class="swiper-slide">
                      <article class="casino-card">
                        @if($bonus->is_hot)
                          <span class="casino-card__ribbon casino-card__ribbon--hot">{{ __('slot.hot') }}</span>
                        @elseif($bonus->is_new)
                          <span class="casino-card__ribbon casino-card__ribbon--new">{{ __('slot.new') }}</span>
                        @endif
                        <div class="casino-card__logo">
                          @if($bonus->logo_path)
                            <img src="{{ media_url($bonus->logo_path) }}" alt="{{ $bonus->casino_name }}" />
                          @else
                            <span class="casino-card__brand">{{ $bonus->casino_name }}</span>
                          @endif
                        </div>
                        <p class="casino-card__primary">
                          <x-site-icon name="casino-gift" class="casino-card__icon--gift" width="17" height="17" />
                          {{ $bonus->short_text }}
                        </p>
                        @if(filled($bonus->extra_text))
                          <p class="casino-card__secondary">
                            <x-site-icon name="casino-star" class="casino-card__icon--star" width="10" height="10" />
                            {{ $bonus->extra_text }}
                          </p>
                        @endif
                        <a class="casino-card__btn" href="{{ $bonus->cta_url ?: '#' }}" rel="sponsored nofollow" @if($bonus->cta_url) target="_blank" @endif>{{ __('slot.play_now') }}</a>
                        <p class="casino-card__terms">{{ __('slot.casino_terms') }}</p>
                      </article>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
            <p class="casino-offers__disclosure">
              <strong>{{ __('slot.advertising_disclosure') }}</strong> {{ __('slot.advertising_disclosure_body') }}
            </p>
          </section>
        @endif

        @if($slot->show_session_data)
          @include('slots.partials.observed')
        @endif

        @if($slot->bonus_features_body)
          <section class="section" id="bonus-features" data-toc-section>
            <h2 class="section__title">{{ __('slot.how_bonus_features_work', ['title' => $title]) }}</h2>
            <div class="section__rich">{!! \App\Support\FeaturePhraseLinker::link((string) $slot->bonus_features_body, $locale ?? null) !!}</div>
          </section>
        @endif

        @if(filled($slot->experience_title) || filled($slot->experience_body))
          <section class="section" id="demo-check" data-toc-section>
            @if(filled($slot->experience_title))
              <h2 class="section__title">{{ $slot->experience_title }}</h2>
            @endif
            @if(filled($slot->experience_body))
              <div class="section__rich">{!! $slot->experience_body !!}</div>
            @endif
          </section>
        @endif

        @if(filled($responsibleIntro) || $responsibleSteps !== [])
          <section class="section how-to" id="how-to-play" data-toc-section>
            <h2 class="section__title">{{ __('slot.how_to_play_responsibly', ['title' => $title]) }}</h2>
            @if(filled($responsibleIntro))
              <p class="section__text section__text--lg">{{ $responsibleIntro }}</p>
            @endif
            @if($responsibleSteps !== [])
              <ol class="how-to__list">
                @foreach($responsibleSteps as $step)
                  <li class="how-to__step">
                    <span class="how-to__num" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}.</span>
                    <div class="how-to__body">
                      <p class="how-to__title">{{ $step['title'] }}</p>
                      @if(filled($step['body'] ?? ($step['desc'] ?? null)))
                        <p class="how-to__desc">{{ $step['body'] ?? $step['desc'] }}</p>
                      @endif
                    </div>
                  </li>
                @endforeach
              </ol>
            @endif
          </section>
        @endif

        @if(filled($slot->symbols_intro) || filled($slot->symbols_mid) || filled($slot->symbols_outro) || $symbols !== [] || $paytableRows !== [])
          <section class="section symbols" id="symbols" data-toc-section>
            <h2 class="section__title">{{ __('slot.symbols_and_paytable') }}</h2>
            @if(filled($slot->symbols_intro))
              <p class="section__text section__text--lg">{{ $slot->symbols_intro }}</p>
            @endif
            @if($symbols !== [])
              <div class="symbols__grid">
                @foreach($symbols as $symbol)
                  <article @class(['symbols__card', 'symbols__card--text' => empty($symbol['image']) && $symbol['payouts'] === null])>
                    <h3 class="symbols__name">{{ $symbol['name'] }}</h3>
                    @if(!empty($symbol['image']))
                      <img class="symbols__img" src="{{ media_url($symbol['image']) }}" alt="" width="88" height="88" loading="lazy" />
                    @endif
                    @if($symbol['payouts'] !== null)
                      <div class="symbols__pays">
                        @foreach($symbol['payouts'] as $payout)
                          <div class="symbols__row">
                            <span class="symbols__mult">{{ $payout['mult'] }}</span>
                            <span class="symbols__val">{{ $payout['val'] }}</span>
                          </div>
                        @endforeach
                      </div>
                    @elseif(filled($symbol['text']))
                      <p class="symbols__desc">{{ $symbol['text'] }}</p>
                    @endif
                  </article>
                @endforeach
              </div>
            @endif
            @if(filled($slot->symbols_mid))
              <p class="section__text symbols__lead">{!! nl2br(preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e($slot->symbols_mid))) !!}</p>
            @endif
            @if($paytableRows !== [])
              <div class="symbols__table-wrap">
                <table class="symbols__table">
                  <thead>
                    <tr>
                      <th scope="col">{{ $paytable['symbol'] }}</th>
                      @foreach($paytable['columns'] as $column)
                        <th scope="col">{{ $column }}</th>
                      @endforeach
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($paytableRows as $row)
                      <tr>
                        <td @class(['symbols__td-symbol' => filled($row['image'])]) @if(filled($row['label'])) aria-label="{{ $row['label'] }}" @endif>
                          @if(filled($row['image']))
                            <img src="{{ media_url($row['image']) }}" alt="" width="30" height="30" />
                          @elseif(filled($row['label']))
                            <span>{{ $row['label'] }}</span>
                          @endif
                        </td>
                        @foreach($paytable['columns'] as $columnIndex => $column)
                          <td>{{ $row['cells'][$columnIndex] ?? '' }}</td>
                        @endforeach
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
            @if(filled($slot->symbols_outro))
              <p class="section__text symbols__outro">{{ $slot->symbols_outro }}</p>
            @endif
          </section>
        @endif

        @if($showRtp)
          <section class="section slot-rtp" id="slot-rtp" data-toc-section>
            <h2 class="section__title">{{ __('slot.rtp_vol_max_title', ['title' => $title]) }}</h2>
            @if($rtpIntro !== '')
              <p class="section__text section__text--lg">{{ $rtpIntro }}</p>
            @endif
            <div class="slot-rtp__stats">
              <div class="slot-rtp__card">
                <span class="slot-rtp__label">{{ __('slot.provider') }}</span>
                <p class="slot-rtp__value">{{ $providerName }}</p>
              </div>
              <div class="slot-rtp__card">
                <span class="slot-rtp__label">{{ __('slot.rtp_range') }}</span>
                <div class="slot-rtp__meter" role="img" aria-label="{{ __('slot.rtp_range_aria', ['from' => $rtpLow ?: __('slot.not_set'), 'to' => $rtpHigh ?: __('slot.not_set')]) }}"></div>
                <div class="slot-rtp__ends">
                  <span>{{ $rtpLow ?: '—' }}</span>
                  <span>{{ $rtpHigh ?: '—' }}</span>
                </div>
              </div>
              <div class="slot-rtp__card">
                <span class="slot-rtp__label">{{ __('slot.volatility') }}</span>
                <div class="slot-rtp__volatility" role="img" aria-label="{{ __('slot.volatility_aria', ['vol' => $slot->volatility ?: __('slot.not_set'), 'bars' => $volBars]) }}">
                  @for($bar = 1; $bar <= 5; $bar++)
                    <span @class(['is-off' => $bar > $volBars])></span>
                  @endfor
                </div>
                <p class="slot-rtp__value slot-rtp__value--level">{{ $slot->volatility ?: '—' }}</p>
              </div>
              <div class="slot-rtp__card">
                <span class="slot-rtp__label">{{ __('slot.max_win') }}</span>
                <p class="slot-rtp__value">{{ $slot->max_win ?: '—' }}</p>
              </div>
            </div>
            @if($interpretCards !== [])
              <h3 class="section__title">{{ __('slot.how_to_interpret') }}</h3>
              <div class="slot-rtp__takes">
                @foreach($interpretCards as $cardIndex => $card)
                  @php $takeTone = ['gold', 'red', 'green'][$cardIndex % 3]; @endphp
                  <article class="slot-rtp__take slot-rtp__take--{{ $takeTone }}">
                    <h4 class="section__heading">{{ $card['title'] }}</h4>
                    <p>{{ $card['body'] }}</p>
                  </article>
                @endforeach
              </div>
            @endif
          </section>
        @endif

        @if($screenshots !== [])
          <section class="section shots" id="screenshots" data-toc-section>
            <h2 class="section__title">{{ __('slot.screenshots_title', ['title' => $title]) }}</h2>
            @if($slot->screenshots_intro)
              <p class="section__text section__text--lg">{{ $slot->screenshots_intro }}</p>
            @endif
            <figure class="shots__stage">
              <button
                type="button"
                class="shots__main-zoom"
                id="shots-open"
                aria-label="{{ __('slot.enlarge_screenshot') }}"
                data-src="{{ $screenshots[0]['url'] }}"
                data-alt="{{ $screenshots[0]['caption'] }}"
              >
                <img
                  class="shots__main"
                  id="shots-main"
                  src="{{ $screenshots[0]['url'] }}"
                  alt="{{ $screenshots[0]['caption'] }}"
                  width="858"
                  height="443"
                />
              </button>
            </figure>
            <div class="shots__thumbs" role="list">
              @foreach($screenshots as $shotIndex => $shot)
                <button
                  type="button"
                  class="shots__thumb{{ $shotIndex === 0 ? ' is-active' : '' }}"
                  role="listitem"
                  data-shot="{{ $shot['url'] }}"
                  data-alt="{{ $shot['caption'] }}"
                  aria-pressed="{{ $shotIndex === 0 ? 'true' : 'false' }}"
                  aria-label="{{ $shot['caption'] }}"
                >
                  <img src="{{ $shot['url'] }}" alt="" width="135" height="81" loading="lazy" />
                  @if(filled($shot['caption']))
                    <span class="shots__caption">{{ $shot['caption'] }}</span>
                  @endif
                </button>
              @endforeach
            </div>
          </section>
        @endif

        @if($mobileShots !== [] || filled($slot->mobile_intro) || $mobileChecklist)
          <section class="section mobile-xp" id="mobile" data-toc-section>
            <h2 class="section__title">{{ __('slot.on_mobile', ['title' => $title]) }}</h2>
            @foreach(preg_split('/\R\s*\R/u', trim((string) $slot->mobile_intro)) ?: [] as $mobilePara)
              @if(filled($mobilePara))
                <p class="section__text section__text--lg">{{ preg_replace('/\s+/u', ' ', trim($mobilePara)) }}</p>
              @endif
            @endforeach
            @if($mobileShots !== [])
              <div class="mobile-xp__shots" role="list">
                @foreach($mobileShots as $shotIndex => $shot)
                  <figure class="mobile-xp__shot{{ $shotIndex === 0 ? ' mobile-xp__shot--active' : '' }}" role="listitem">
                    <button type="button" class="mobile-xp__frame js-open-lightbox" data-src="{{ $shot['url'] }}" data-alt="{{ $shot['caption'] }}" aria-label="{{ filled($shot['caption']) ? $shot['caption'] : __('slot.enlarge_screenshot') }}">
                      <img src="{{ $shot['url'] }}" alt="" width="138" height="245" loading="lazy" />
                    </button>
                    @if(filled($shot['caption']))
                      <figcaption class="mobile-xp__caption">{{ $shot['caption'] }}</figcaption>
                    @endif
                  </figure>
                @endforeach
              </div>
            @endif
            @if($mobileChecklist)
              <h3 class="section__title">{{ __('slot.mobile_checklist') }}</h3>
              <ul class="section__list">
                @foreach($mobileChecklist as $item)
                  @php
                    $checkTitle = null;
                    $checkBody = $item;
                    if (preg_match('/^([^:]{1,48}):\s*(.+)$/us', $item, $checkMatch) === 1) {
                        $checkTitle = $checkMatch[1];
                        $checkBody = $checkMatch[2];
                    }
                  @endphp
                  <li>
                    <x-site-icon name="check-green" class="section__check" width="16" height="12" />
                    <p>
                      @if(filled($checkTitle))
                        <strong>{{ $checkTitle }}:</strong>
                        {{ $checkBody }}
                      @else
                        {{ $checkBody }}
                      @endif
                    </p>
                  </li>
                @endforeach
              </ul>
            @endif
          </section>
        @endif

        <section class="section similar" id="similar-slots" data-toc-section>
          @php
            $similarHeading = __('slot.similar_heading', ['title' => $title, 'provider' => $providerName]);
            $similarRows = collect([$slot])->concat($similar);
          @endphp
          <h2 class="section__title">{{ $similarHeading }}</h2>
          @if($slot->similar_intro)
            <p class="section__text section__text--lg">{{ $slot->similar_intro }}</p>
          @endif
          <div class="similar__table-wrap">
            <table class="similar__table">
              <caption>{{ $similarHeading }}</caption>
              <colgroup>
                <col class="similar__col similar__col--slot" />
                <col class="similar__col similar__col--return" />
                <col class="similar__col similar__col--vol" />
                <col class="similar__col similar__col--bonus" />
                <col class="similar__col similar__col--watch" />
              </colgroup>
              <thead>
                <tr>
                  <th scope="col">{{ __('slot.similar_col_slot') }}</th>
                  <th scope="col">{{ __('slot.similar_col_return') }}</th>
                  <th scope="col">{{ __('slot.similar_col_vol') }}</th>
                  <th scope="col">{{ __('slot.similar_col_bonus') }}</th>
                  <th scope="col">{{ __('slot.similar_col_watch') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($similarRows as $item)
                  @php
                    $isCurrent = $item->is($slot);
                    $itemTitle = $item->displayTitle();
                  @endphp
                  <tr>
                    <th scope="row">
                      @if($isCurrent)
                        {{ $itemTitle }}
                      @else
                        <a href="{{ rtrim($item->publicUrl(), '/').'/' }}">{{ $itemTitle }}</a>
                      @endif
                    </th>
                    <td>{{ $item->similarReturnLabel() }}</td>
                    <td>{{ $item->volatility ?: '—' }}</td>
                    <td>{!! nl2br(e($item->similar_bonus_fit ?: '—')) !!}</td>
                    <td>{!! nl2br(e($item->similar_watch_out ?: '—')) !!}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          @if($slot->similar_outro || $providerGuide)
            <h3 class="section__heading">{{ __('slot.read_more_provider', ['provider' => $providerName]) }}</h3>
            @if($slot->similar_outro)
              <p class="section__text">{{ $slot->similar_outro }}</p>
            @endif
            @if($providerGuide)
              @php
                $guideCover = $providerGuide->coverUrl();
                $guideDate = $providerGuide->published_at ?: $providerGuide->created_at;
              @endphp
              <a class="similar__article" href="{{ rtrim($providerGuide->publicUrl(), '/').'/' }}">
                @if($guideCover)
                  <img
                    class="similar__article-img"
                    src="{{ $guideCover }}"
                    alt=""
                    width="180"
                    height="90"
                  />
                @endif
                <span class="similar__article-body">
                  <span class="similar__article-title">{{ $providerGuide->displayTitle() }}</span>
                  @if($guideDate)
                    <span class="similar__article-date">{{ $guideDate->translatedFormat('F j, Y') }}</span>
                  @endif
                </span>
                <span class="similar__article-go" aria-hidden="true">
                  <x-site-icon name="similar-article-go" width="14" height="8" />
                </span>
              </a>
            @endif
          @endif

          @if($slot->similar_new_intro || $providerSlots->isNotEmpty())
            <h3 class="section__heading">{{ __('slot.new_provider_slots', ['provider' => $providerName, 'title' => $title]) }}</h3>
            @if($slot->similar_new_intro)
              <p class="section__text">{{ $slot->similar_new_intro }}</p>
            @endif

            @if($providerSlots->isNotEmpty())
              <div class="similar__picks-head">
                <h3 class="section__heading">{{ __('slot.fresh_picks', ['provider' => $providerName]) }}</h3>
                <a class="similar__show-all" href="{{ $providerUrl }}">{{ __('slot.show_all') }}</a>
              </div>

              <div class="similar__carousel">
                <div class="swiper" id="similar-swiper">
                  <div class="swiper-wrapper">
                    @foreach($providerSlots as $pick)
                      <div class="swiper-slide">
                        <article class="similar__game">
                          <a class="similar__game-media" href="{{ rtrim($pick->publicUrl(), '/').'/' }}">
                            @if($pick->aboutCoverUrl())
                              <img
                                src="{{ $pick->aboutCoverUrl() }}"
                                alt="{{ $pick->displayTitle() }}"
                                width="172"
                                height="224"
                              />
                            @endif
                            <x-site-icon name="similar-mini-logo" class="similar__game-logo" width="131" height="24" />
                            @if($pick->rtpPercentLabel())
                              <span class="similar__game-rtp">RTP {{ $pick->rtpPercentLabel() }}</span>
                            @endif
                          </a>
                          <p class="similar__game-name">{{ $pick->displayTitle() }}</p>
                        </article>
                      </div>
                    @endforeach
                  </div>
                </div>
                <button
                  class="similar__nav similar__nav--prev"
                  type="button"
                  aria-label="{{ __('slot.previous_games') }}"
                  data-similar-prev
                >
                  <x-site-icon name="similar-nav" width="20" height="20" />
                </button>
                <button
                  class="similar__nav similar__nav--next"
                  type="button"
                  aria-label="{{ __('slot.next_games') }}"
                  data-similar-next
                >
                  <x-site-icon name="similar-nav" width="20" height="20" />
                </button>
              </div>
            @endif
          @endif

          @if($slot->similar_note)
            <p class="similar__note">{{ $slot->similar_note }}</p>
          @endif
        </section>

        @if($slot->showsMethodology())
          @php
            $methodologyPoints = $slot->methodologyPoints();
          @endphp
          <section class="section" id="methodology" data-toc-section>
            <h2 class="section__title">{{ __('slot.methodology_title', ['title' => $title]) }}</h2>
            @if($slot->methodology_status)
              <p class="section__text methodology__status">
                <strong>{{ __('slot.publication_status') }}</strong>
                {{ $slot->methodology_status }}
              </p>
            @endif
            @if($methodologyPoints)
              <ul class="section__bullets methodology__list">
                @foreach($methodologyPoints as $point)
                  <li>
                    <p>
                      @if(filled($point['title']))
                        <strong>{{ rtrim($point['title'], '.').'.' }}</strong>
                      @endif
                      {{ $point['body'] }}
                    </p>
                  </li>
                @endforeach
              </ul>
            @elseif($slot->methodology_body)
              <div class="section__rich">{!! $slot->methodology_body !!}</div>
            @endif
            <aside class="section__support methodology__support">
              <p>
                <strong>{{ __('slot.need_support') }}</strong>
                {!! __('slot.need_support_body', ['link' => '<a href="https://www.gamblingtherapy.org/" target="_blank" rel="noopener noreferrer">'.e(__('slot.gambling_therapy')).'</a>']) !!}
              </p>
            </aside>
          </section>
        @endif

        <section class="section reader-reviews" id="player-reviews" aria-labelledby="player-reviews-title" data-toc-section>
          <header class="reader-reviews__head">
            <div>
              <p class="reader-reviews__eyebrow">{{ __('slot.player_reviews') }}</p>
              <h2 class="section__title" id="player-reviews-title">{{ __('slot.player_reviews_for', ['title' => $title]) }}</h2>
              <p class="reader-reviews__intro">
                {{ __('slot.player_reviews_intro') }}
              </p>
            </div>
            <div class="reader-reviews__score">
              <strong id="reviews-avg-display">{{ $avg !== null ? number_format($avg, 1) : '—' }}</strong>
              <div>
                <span class="reader-reviews__stars" role="img" aria-label="{{ $avg !== null ? __('slot.average_rating_aria', ['score' => number_format($avg, 1)]) : __('slot.average_rating_none') }}">
                  @for($i = 1; $i <= 5; $i++)
                    <x-site-icon :name="$i <= $stars($avg) ? 'star' : 'star-outline-orange'" width="18" height="18" />
                  @endfor
                </span>
                <small id="reviews-count-display">{{ trans_choice('slot.based_on_reviews', $reviewsCount, ['count' => $reviewsCount]) }}</small>
              </div>
            </div>
          </header>

          <aside class="section__take" aria-label="{{ __('slot.how_reviews_work') }}">
            <p>
              <strong>{{ __('slot.how_reviews_work') }}</strong>
              {!! __('slot.how_reviews_work_body', ['link' => '<a href="#methodology">'.e(__('slot.our_methodology')).'</a>']) !!}
            </p>
          </aside>

          <div class="reader-reviews__toolbar">
            <div class="reader-reviews__filters" role="group" aria-label="{{ __('slot.filter_player_reviews') }}">
              <button class="is-active" type="button" aria-pressed="true" data-rating-filter="all">{{ __('slot.all_reviews') }}</button>
              <button type="button" aria-pressed="false" data-rating-filter="demo">{{ __('slot.demo_play') }}</button>
              <button type="button" aria-pressed="false" data-rating-filter="real">{{ __('slot.real_money') }}</button>
            </div>
            <button
              class="reader-reviews__rate {{ $authUser ? 'js-open-rank-modal' : 'js-open-auth' }}"
              type="button"
              aria-haspopup="dialog"
              @unless($authUser) data-after-login="rate" @endunless
            >{{ __('slot.write_a_review') }}</button>
          </div>

          <div class="reader-reviews__summary">
            <article class="reader-reviews__distribution" aria-label="{{ __('slot.filter_by_rating') }}">
              <h3>{{ __('slot.rating_breakdown') }}</h3>
              @for($i = 5; $i >= 1; $i--)
                @php
                  $bucket = $ratingBuckets[$i] ?? 0;
                  $pct = $reviewsCount > 0 ? round(($bucket / $reviewsCount) * 100) : 0;
                @endphp
                <button
                  class="reader-reviews__star-filter"
                  type="button"
                  data-star-filter="{{ $i }}"
                  aria-pressed="false"
                >
                  <span>{{ $i }}</span>
                  <i><b style="width: {{ $pct }}%"></b></i>
                  <small>{{ $pct }}%</small>
                </button>
              @endfor
            </article>
          </div>

          <div class="reader-reviews__list" id="slot-reviews-list">
            @forelse($reviewCards as $review)
              @php
                $reviewLiked = $authUser && $review->liked_by_me;
                $modeLabel = $review->play_mode === 'real' ? __('slot.real_money') : __('slot.demo_play');
              @endphp
              <article
                class="reader-review {{ in_array($review->id, $wideReviewIds, true) ? 'reader-review--wide' : '' }} {{ in_array($review->id, $visibleReviewIds, true) ? '' : 'is-paged-out' }}"
                data-rating-source="{{ $review->play_mode }}"
                data-review-rating="{{ (int) $review->rating }}"
                data-review-id="{{ $review->id }}"
              >
                <div class="reader-review__top">
                  <img src="{{ $review->user?->avatarUrl() ?: asset('assets/images/header/profile.svg') }}" alt="" width="44" height="44" />
                  <div>
                    <strong>{{ $review->user?->displayName() ?: __('slot.player') }}</strong>
                    <span>{{ $modeLabel }}{{ $review->played_myself ? __('slot.verified') : '' }}</span>
                  </div>
                  <b>{{ number_format((float) $review->rating, 1) }}</b>
                </div>
                @if($review->body)
                  <p class="reader-review__copy">{{ $review->body }}</p>
                @endif
                <footer>
                  <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->translatedFormat('F j, Y') }}</time>
                  <button
                    type="button"
                    class="reader-review__like {{ $authUser ? 'js-like-review' : 'js-open-auth' }} {{ $reviewLiked ? 'is-liked' : '' }}"
                    data-review-id="{{ $review->id }}"
                    data-like-url="{{ route('slots.reviews.like', $review) }}"
                  >
                    {{ explode(':count', __('slot.helpful', ['count' => ':count']))[0] }}<span class="js-like-count">{{ (int) $review->likes_count }}</span>{{ explode(':count', __('slot.helpful', ['count' => ':count']))[1] ?? '' }}
                  </button>
                </footer>
              </article>
            @empty
              <p class="section__text" id="reviews-empty">{{ __('slot.no_reviews_yet') }}</p>
            @endforelse
          </div>
          @if($hiddenReviewCount > 0)
            @php $nextBatch = min(10, $hiddenReviewCount); @endphp
            <button class="reader-reviews__show-all" id="reviews-more" type="button">
              <x-site-icon name="reviews-chevron-down" width="16" height="16" />
              <span>{{ __('slot.show_next', ['count' => $nextBatch]) }}</span>
              <x-site-icon name="reviews-chevron-down" width="16" height="16" />
            </button>
          @endif

          <footer class="reader-reviews__foot">
            <p>
              {{ __('slot.reviews_foot') }}
            </p>
            <a href="#methodology">{{ __('slot.how_reviews_moderated') }}</a>
          </footer>
        </section>

        @if($slot->showsEditorialPledge())
          @php
            $pledgeAuthor = $slot->author?->displayName();
            $pledgeRole = $slot->editorialRoleLabel();
            $pledgeReviewer = $slot->reviewer?->displayName();
            $pledgeReviewedOn = $slot->last_reviewed_on;
            $pledgeUpdatedOn = $slot->content_updated_on ?: $pledgeReviewedOn;
            $pledgeFocus = trim((string) $slot->review_focus);
          @endphp
          <section class="editorial" aria-label="{{ __('slot.editorial_pledge') }}">
            <div class="editorial__inner">
              <div class="editorial__person">
                <div class="editorial__meta">
                  <a class="editorial__label" href="#methodology">{{ __('slot.editorial_pledge') }}</a>
                  @if($pledgeAuthor)
                    <p class="editorial__name">
                      @if($slot->author->is_published)
                        <a href="{{ rtrim($slot->author->publicUrl($locale), '/').'/' }}">{{ $pledgeAuthor }}</a>
                      @else
                        {{ $pledgeAuthor }}
                      @endif
                    </p>
                  @endif
                  @if($pledgeRole)
                    <p class="editorial__role">{{ $pledgeRole }}</p>
                  @endif
                </div>
              </div>
              <div class="editorial__stats">
                @if($pledgeReviewer)
                  <div class="editorial__stat">
                    <span class="editorial__stat-label">{{ __('slot.reviewed_by') }}</span>
                    <span class="editorial__stat-value">{{ $pledgeReviewer }}</span>
                  </div>
                @endif
                @if($pledgeReviewedOn)
                  <div class="editorial__stat">
                    <span class="editorial__stat-label">{{ __('slot.last_reviewed') }}</span>
                    <time class="editorial__stat-value" datetime="{{ $pledgeReviewedOn->toDateString() }}">{{ $pledgeReviewedOn->translatedFormat('F j, Y') }}</time>
                  </div>
                @endif
                @if($pledgeUpdatedOn)
                  <div class="editorial__stat">
                    <span class="editorial__stat-label">{{ __('slot.last_updated') }}</span>
                    <time class="editorial__stat-value" datetime="{{ $pledgeUpdatedOn->toDateString() }}">{{ $pledgeUpdatedOn->translatedFormat('F j, Y') }}</time>
                  </div>
                @endif
                @if($pledgeFocus !== '')
                  <div class="editorial__stat">
                    <span class="editorial__stat-label">{{ __('slot.review_focus') }}</span>
                    <span class="editorial__stat-value">{{ $pledgeFocus }}</span>
                  </div>
                @endif
              </div>
            </div>
          </section>
        @endif

        @if($faq)
          <section class="section faq" id="faq" data-toc-section>
            <h2 class="section__title">{{ __('slot.faqs') }}</h2>
            <div class="faq__list">
              @foreach($faq as $item)
                <details class="faq__item">
                  <summary class="faq__summary">
                    <span class="faq__question">{{ $item['question'] ?? ($item['q'] ?? __('slot.question')) }}</span>
                    <x-site-icon name="faq-chevron" class="faq__chevron" width="16" height="16" />
                  </summary>
                  <div class="faq__panel">
                    <p class="faq__answer">{{ $item['answer'] ?? ($item['a'] ?? '') }}</p>
                  </div>
                </details>
              @endforeach
            </div>
          </section>
        @endif
      </div>

      <aside class="toc" aria-label="{{ __('slot.on_this_page') }}">
        <nav class="toc__nav" aria-label="{{ __('slot.on_this_page_links') }}">
          @foreach($toc as $index => $item)
            <a class="toc__link {{ $index === 0 ? 'toc__link--active' : '' }}" href="{{ $item['href'] }}">
              <x-site-icon :name="$item['icon']" class="toc__glyph" width="17" height="17" />
              <span>{{ $item['label'] }}</span>
            </a>
          @endforeach
        </nav>
      </aside>
    </div>
  </div>

  <div
    class="rank-modal"
    id="rank-modal"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="rank-modal-title"
    data-review-url="{{ rtrim(localized_url($locale, 'slots/'.$slot->slug), '/').'/reviews' }}"
    data-authenticated="{{ $authUser ? '1' : '0' }}"
    data-initial-rating="{{ $myReview?->rating ?? 4 }}"
    data-initial-mode="{{ $myReview?->play_mode ?? 'demo' }}"
    data-initial-played="{{ $myReview?->played_myself ? '1' : '0' }}"
    data-initial-body="{{ str_replace(["\r", "\n"], ' ', (string) ($myReview?->body ?? '')) }}"
  >
    <div class="rank-modal__backdrop js-close-rank-modal" data-close></div>
    <div class="rank-modal__dialog">
      <button class="rank-modal__close js-close-rank-modal" type="button" aria-label="{{ __('slot.close') }}">
        <x-site-icon name="close-square" width="24" height="24" />
      </button>
      <div class="rank-modal__logo">
        <img src="/assets/icons/logo-7.svg" alt="" width="32" height="22" />
        <span>slots.tube</span>
      </div>
      <p class="rank-modal__title" id="rank-modal-title">{{ __('slot.rate_title', ['title' => $title]) }}</p>
      <div id="rank-modal-fields">
      @if($authUser)
        <p class="rank-modal__user">
          @if($authUser->avatar_path ?? false)
            <img src="{{ media_url($authUser->avatar_path) }}" alt="" width="44" height="44" />
          @else
            <span class="rank-modal__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) $authUser->name, 0, 1)) }}</span>
          @endif
          <span class="rank-modal__name">{{ $authUser->name }}</span>
        </p>
      @endif
      <div class="rank-modal__mode">
        <span>{{ __('slot.where_did_you_try') }}</span>
        <div role="group" aria-label="{{ __('slot.play_mode') }}">
          <button class="is-active" type="button" aria-pressed="true" data-rating-mode="demo">{{ __('slot.demo') }}</button>
          <button type="button" aria-pressed="false" data-rating-mode="real">{{ __('slot.real_money') }}</button>
        </div>
      </div>
      <div class="rank-modal__stars" id="rank-modal-stars" role="group" aria-label="{{ __('slot.star_rating') }}">
        @for($i = 1; $i <= 5; $i++)
          <button class="rank-modal__star" type="button" data-value="{{ $i }}" aria-label="{{ trans_choice('slot.star', $i, ['count' => $i]) }}" aria-pressed="{{ $i <= 4 ? 'true' : 'false' }}">
            <x-site-icon :name="$i <= 4 ? 'star' : 'star-outline-orange'" width="28" height="28" />
          </button>
        @endfor
      </div>
      <div class="rank-modal__stepper">
        <button type="button" class="rank-modal__step" id="rank-minus" aria-label="{{ __('slot.decrease_rating') }}">−</button>
        <span class="rank-modal__value" id="rank-value">4.0</span>
        <button type="button" class="rank-modal__step" id="rank-plus" aria-label="{{ __('slot.increase_rating') }}">+</button>
      </div>
      <label class="rank-modal__evidence">
        <input type="checkbox" id="rank-evidence" />
        <span>{{ __('slot.played_myself') }}</span>
      </label>
      <label class="rank-modal__comment">
        <span>{{ __('slot.what_shaped_rating') }} <small>{{ __('slot.optional') }}</small></span>
        <textarea id="rank-comment" rows="3" maxlength="5000" placeholder="{{ __('slot.comment_placeholder') }}"></textarea>
      </label>
      <p class="rank-modal__note">{{ __('slot.reviews_help') }}</p>
      <p class="rank-modal__status" id="rank-prototype-status" role="status" aria-live="polite"></p>
      <button class="btn btn--accept" type="button" id="rank-accept">{{ __('slot.submit_rating') }}</button>
      </div>
      <div class="rank-modal__thanks" id="rank-modal-thanks" hidden>
        <svg class="rank-modal__thumb" viewBox="0 0 24 24" width="48" height="48" aria-hidden="true">
          <path fill="currentColor" d="M2 21h4V9H2v12zm20-11a2 2 0 0 0-2-2h-6.31l.95-4.57.03-.32a1.5 1.5 0 0 0-.44-1.06L13.17 1 6.59 7.59A2 2 0 0 0 6 9v10a2 2 0 0 0 2 2h9a2 2 0 0 0 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/>
        </svg>
        <p>{{ __('slot.thank_you_feedback') }}</p>
      </div>
    </div>
  </div>

  @if($slot->demo_url)
    <div class="demo-modal" id="demo-modal" hidden>
      <div class="demo-modal__backdrop js-close-demo"></div>
      <div class="demo-modal__dialog" role="dialog" aria-modal="true" aria-label="{{ __('slot.play_for_free_aria', ['title' => $title]) }}">
        <button class="demo-modal__close js-close-demo" type="button" aria-label="{{ __('slot.close') }}">
          <x-site-icon name="close-square" width="24" height="24" />
        </button>
        <div class="demo-modal__frame">
          <iframe
            id="demo-modal-frame"
            title="{{ __('slot.demo_iframe_title', ['title' => $title]) }}"
            data-src="{{ $slot->demo_url }}"
            allow="autoplay; fullscreen; encrypted-media"
            referrerpolicy="no-referrer-when-downgrade"
          ></iframe>
          @if(!empty($gamePromo))
            @php
              $promoOffer = $gamePromo->translated('offer_text', $locale);
              $promoCta = $gamePromo->translated('cta_label', $locale) ?: __('slot.game_promo_cta');
              $promoLegal = $gamePromo->translated('legal_text', $locale) ?: __('slot.game_promo_legal');
            @endphp
            <div
              class="game-promo"
              id="game-promo"
              hidden
              data-delay="{{ $gamePromo->delayMs() }}"
              role="dialog"
              aria-modal="true"
              aria-label="{{ __('slot.game_promo_aria') }}"
            >
              <div class="game-promo__scrim"></div>
              <div class="game-promo__card">
                <button class="game-promo__close js-close-game-promo" type="button" aria-label="{{ __('slot.game_promo_close') }}">
                  <span aria-hidden="true">×</span>
                </button>
                <div class="game-promo__logo">
                  @if($gamePromo->logo_path)
                    <img src="{{ media_url($gamePromo->logo_path) }}" alt="{{ $gamePromo->casino_name }}" width="180" height="64" />
                  @else
                    <strong>{{ $gamePromo->casino_name }}</strong>
                  @endif
                </div>
                <div class="game-promo__offer">
                  <span class="game-promo__offer-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="11" fill="#fff"/><path d="M12 6.5v11M9 9.2c.6-1 1.6-1.5 3-1.5 1.7 0 3 1 3 2.4S13.7 12.5 12 12.5 9 13.4 9 14.9c0 1.5 1.4 2.5 3.2 2.5 1.4 0 2.4-.5 3-1.5" stroke="#1a1d29" stroke-width="1.8" stroke-linecap="round"/></svg>
                  </span>
                  <p>{{ $promoOffer }}</p>
                </div>
                <a class="game-promo__cta" href="{{ $gamePromo->cta_url }}" target="_blank" rel="noopener sponsored nofollow">
                  <span class="game-promo__cta-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 10h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V10Z" fill="#fff" fill-opacity=".95"/><path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h15A1.5 1.5 0 0 1 21 7.5V10H3V7.5Z" fill="#fff"/><path d="M12 6V22M8.5 6c0-1.7 1.3-3 3.5-3s3.5 1.3 3.5 3" stroke="#e11d2e" stroke-width="1.6" stroke-linecap="round"/></svg>
                  </span>
                  {{ $promoCta }}
                </a>
                <p class="game-promo__legal">{{ $promoLegal }}</p>
              </div>
            </div>
          @endif
        </div>
        @if($demoOffer)
          <a class="demo-modal__offer" href="{{ $demoOffer->cta_url ?: '#' }}" target="_blank" rel="noopener sponsored nofollow">
            @if($demoOffer->logo_path)
              <img src="{{ media_url($demoOffer->logo_path) }}" alt="" width="40" height="40" />
            @endif
            <span>
              <strong>{{ $demoOffer->casino_name ?: $demoOffer->displayTitle() }}</strong>
              <em>{{ $demoOffer->getTranslation('short_text', $locale) ?: $demoOffer->getTranslation('short_text', 'en') }}</em>
            </span>
            @if($demoOffer->is_hot)<b>{{ __('slot.hot') }}</b>@endif
            @if($demoOffer->is_new)<b>{{ __('slot.new') }}</b>@endif
          </a>
        @endif
      </div>
    </div>
  @endif

  <div class="image-lightbox" id="image-lightbox" hidden role="dialog" aria-modal="true" aria-label="{{ __('slot.enlarged_screenshot') }}">
    <div class="image-lightbox__backdrop js-close-lightbox" tabindex="-1"></div>
    <button type="button" class="image-lightbox__nav image-lightbox__nav--prev" id="image-lightbox-prev" aria-label="{{ __('slot.previous_screenshot') }}">‹</button>
    <button type="button" class="image-lightbox__close js-close-lightbox" aria-label="{{ __('slot.close') }}">×</button>
    <button type="button" class="image-lightbox__nav image-lightbox__nav--next" id="image-lightbox-next" aria-label="{{ __('slot.next_screenshot') }}">›</button>
    <img class="image-lightbox__img" id="image-lightbox-img" src="" alt="" />
  </div>
@endsection

@push('scripts')
  <script>
    window.SlotPage = {
      authenticated: @json((bool) $authUser),
      reviewUrl: @json(rtrim(localized_url($locale, 'slots/'.$slot->slug), '/').'/reviews'),
      translateUrl: @json(url('/translate')),
      csrf: @json(csrf_token()),
      i18n: {
        demoPlay: @json(__('slot.demo_play')),
        realMoney: @json(__('slot.real_money')),
        player: @json(__('slot.player')),
        helpful: @json(__('slot.helpful', ['count' => ':count'])),
        showNext: @json(__('slot.show_next', ['count' => ':count'])),
        showNext10: @json(__('slot.show_next_10')),
        liveWindow: @json(__('slot.obs_live_window', ['window' => ':window'])),
        window1h: @json(__('slot.obs_window_1h')),
        window24h: @json(__('slot.obs_window_24h')),
        window7d: @json(__('slot.obs_window_7d')),
        playersLine: @json(__('slot.obs_players_line', ['count' => ':count'])),
        enlargeScreenshot: @json(__('slot.enlarge_screenshot')),
      },
    };
  </script>
  <script src="/js/ugc-translate.js?v=20261008-tr1" defer></script>
  <script src="/js/slot-page.js?v=20261008-icons1" defer></script>
@endpush
