@extends('layouts.app')

@php
  $heroParagraphs = array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $page->text('hero_text')))));
  $panelItems = array_values(array_filter([
    ['icon' => 'bonus-hero-shield', 'title' => $page->text('panel_1_title'), 'text' => $page->text('panel_1_text')],
    ['icon' => 'bonus-hero-gift', 'title' => $page->text('panel_2_title'), 'text' => $page->text('panel_2_text')],
    ['icon' => 'bonus-hero-secure', 'title' => $page->text('panel_3_title'), 'text' => $page->text('panel_3_text')],
  ], fn (array $item): bool => $item['title'] !== '' || $item['text'] !== ''));
  $panelTitle = $page->text('panel_title');
  $metaDescription = \Illuminate\Support\Str::limit($heroParagraphs[0] ?? $page->text('subtitle'), 155);
  $featureIcons = [
    'regular_offers' => 'bonus-feature-regular-offers',
    'live_casino' => 'bonus-feature-live-casino',
    'live_chat' => 'bonus-feature-live-chat',
    'vip_program' => 'bonus-feature-vip-program',
  ];
@endphp

@include('partials.page-seo', [
  'page' => $page,
  'title' => __('bonus.page.meta_title'),
  'description' => $metaDescription,
  'canonical' => $canonical,
])

@push('head')
  <script type="application/ld+json">{!! json_encode(\App\Support\BonusSchema::page(
    $canonical,
    $page->metaTitle(__('bonus.page.meta_title')),
    $page->metaDescription($metaDescription),
    $bonuses,
  ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
  <div class="main--bonuses">
    <div class="container">
      <section class="page-intro page-intro--center" aria-labelledby="bonuses-title">
        <h1 class="page-intro__title" id="bonuses-title">{{ $page->text('title') }} <span class="page-intro__accent">{{ $page->text('title_accent') }}</span></h1>
        @if($page->text('subtitle') !== '')
          <p class="page-intro__subtitle">{{ $page->text('subtitle') }}</p>
        @endif
      </section>

      <section class="bonus-hero" aria-labelledby="bonus-hero-title">
        <div class="bonus-hero__media" aria-hidden="true"></div>
        <div class="bonus-hero__inner">
          <div class="bonus-hero__copy">
            <div class="bonus-hero__heading">
              <x-site-icon name="bonus-hero-bell" class="bonus-hero__bell" width="28" height="28" />
              <h2 class="bonus-hero__title" id="bonus-hero-title">{{ $page->text('hero_title') }}</h2>
            </div>
            <span class="bonus-hero__rule" aria-hidden="true"></span>
            @foreach($heroParagraphs as $paragraph)
              <p class="bonus-hero__text">{{ $paragraph }}</p>
            @endforeach
          </div>
          @if($panelTitle !== '' || $panelItems !== [])
            <aside class="bonus-hero__panel">
              @if($panelTitle !== '')
                <h3 class="bonus-hero__panel-title">{{ $panelTitle }}</h3>
              @endif
              <ul class="bonus-hero__list">
                @foreach($panelItems as $item)
                  <li>
                    <span class="bonus-hero__icon" aria-hidden="true"><x-site-icon :name="$item['icon']" width="16" height="16" /></span>
                    <span class="bonus-hero__item">
                      @if($item['title'] !== '')<strong>{{ $item['title'] }}</strong>@endif
                      @if($item['text'] !== '')<span>{{ $item['text'] }}</span>@endif
                    </span>
                  </li>
                @endforeach
              </ul>
            </aside>
          @endif
        </div>
      </section>

      <section class="bonus-list" aria-label="{{ __('bonus.list_label') }}">
        @forelse($bonuses as $bonus)
          @php
            $ribbon = $bonus->no_kyc ? __('bonus.no_kyc') : ($bonus->is_exclusive ? __('bonus.exclusive') : null);
            $features = $bonus->featureKeys();
          @endphp
          <article class="bonus-offer">
            <div class="bonus-offer__logo">
              @if($ribbon)
                <span class="bonus-offer__ribbon">{{ $ribbon }}</span>
              @endif
              @if($bonus->logo_path)
                <img src="{{ media_url($bonus->logo_path) }}" alt="{{ $bonus->casino_name }}" width="112" height="112" loading="lazy" />
              @else
                <span class="bonus-offer__brand">{{ $bonus->casino_name }}</span>
              @endif
            </div>
            <div class="bonus-offer__info">
              <p class="bonus-offer__casino">{{ $bonus->casino_name }}</p>
              @if($bonus->offerTitle() !== '')
                <h2 class="bonus-offer__title">{{ $bonus->offerTitle() }}</h2>
              @endif
            </div>
            @if($features !== [])
              <div class="bonus-offer__tags">
                @foreach($features as $feature)
                  <span class="bonus-offer__tag"><x-site-icon :name="$featureIcons[$feature]" width="24" height="24" />{{ __('bonus.features.'.$feature) }}</span>
                @endforeach
              </div>
            @endif
            <div class="bonus-offer__cta">
              <a class="btn btn--cta bonus-offer__btn" href="{{ $bonus->cta_url ?: '#' }}" rel="sponsored nofollow noopener" @if($bonus->cta_url) target="_blank" @endif>{{ __('bonus.get_bonus') }}</a>
              @if(filled($bonus->website_url))
                <a class="bonus-offer__site" href="{{ $bonus->website_url }}" rel="nofollow noopener" target="_blank">{{ __('bonus.go_to_website') }}</a>
              @endif
            </div>
            <p class="bonus-offer__terms">{{ __('bonus.terms') }}</p>
          </article>
        @empty
          <p class="bonus-list__empty">{{ __('bonus.empty') }}</p>
        @endforelse
      </section>

      @include('partials.page-about', ['blocks' => $page->blocksFor()])

      @include('content.partials.faq', ['faq' => $page->faqFor()])
    </div>
  </div>
@endsection
