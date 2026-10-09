@extends('layouts.app')

@include('partials.page-seo', [
  'page' => $page,
  'title' => __('content.meta_title'),
  'description' => __('content.meta_description'),
  'canonical' => $canonical,
])

@push('head')
  <script type="application/ld+json">{!! json_encode(\App\Support\ContentSchema::hub(
    $canonical,
    $page->metaTitle(__('content.meta_title')),
    $page->metaDescription(__('content.meta_description')),
    $top->concat($popular)->concat($latest)->concat($guides),
    $sections,
  ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
  <div class="main--news">
    <div class="container">
      <header class="page-intro page-intro--center page-intro--news">
        <h1 class="page-intro__title page-intro__title--news">{{ __('content.title') }} <span class="page-intro__accent">{{ __('content.title_accent') }}</span></h1>
        <p class="page-intro__subtitle">{{ __('content.subtitle') }}</p>
      </header>

      @if($latest->isEmpty())
        <p class="news-section-head__text" style="text-align:center;padding:40px 0 80px;">{{ __('content.empty') }}</p>
      @else
        <section class="news-top" aria-labelledby="top-news-title">
          <div class="news-top__featured">
            <div class="news-section-head">
              <h2 class="news-section-head__title" id="top-news-title">{{ __('content.top_title') }}</h2>
              <p class="news-section-head__text">{{ __('content.top_text') }}</p>
            </div>
            <div class="news-top__cards">
              @foreach($top as $post)
                @include('content.partials.card', ['post' => $post])
              @endforeach
            </div>
          </div>

          @if($popular->isNotEmpty())
            <aside class="news-popular" aria-labelledby="popular-news-title">
              <h2 class="news-popular__title" id="popular-news-title">{{ __('content.popular_title') }}</h2>
              <div class="news-popular__list">
                @foreach($popular as $post)
                  @php($popularCover = $loop->first ? $post->coverUrl() : null)
                  <a class="news-popular__item {{ $popularCover ? 'news-popular__item--featured' : '' }}" href="{{ rtrim($post->publicUrl(), '/') }}/">
                    @if($popularCover)
                      <img class="news-popular__cover" src="{{ $popularCover }}" alt="" width="335" height="90" loading="lazy" />
                    @endif
                    <span class="news-popular__text">{{ $post->displayTitle() }}</span>
                  </a>
                @endforeach
              </div>
            </aside>
          @endif
        </section>

        <hr class="news-divider" />

        <section class="news-panel news-latest" aria-labelledby="latest-news-title">
          <div class="news-section-head">
            <h2 class="news-section-head__title" id="latest-news-title">{{ __('content.latest_title') }}</h2>
            <p class="news-section-head__text">{{ __('content.latest_text') }}</p>
          </div>

          <nav class="category-mosaic category-mosaic--news" aria-label="{{ __('content.sections_aria') }}">
            <div class="category-mosaic__grid">
              @foreach($sections as $section)
                <a class="category-mosaic__pill" href="{{ rtrim($section['url'], '/') }}/"><x-site-icon :name="$section['icon']" class="category-mosaic__icon" width="22" height="22" /><span class="category-mosaic__label">{{ $section['label'] }}</span></a>
              @endforeach
            </div>
          </nav>

          <div class="author-news author-news--page">
            @foreach($latest as $post)
              @include('content.partials.card', ['post' => $post])
            @endforeach
          </div>

          <div class="news-cta-wrap">
            <a class="news-cta" href="{{ rtrim(localized_url(null, 'content/news'), '/') }}/">{{ __('content.explore_news') }}</a>
          </div>
        </section>

        @if($guides->isNotEmpty())
          <section class="news-panel news-guides" aria-labelledby="news-guides-title">
            <div class="news-section-head">
              <h2 class="news-section-head__title" id="news-guides-title">{{ __('content.guides_title') }}</h2>
              <p class="news-section-head__text">{{ __('content.guides_text') }}</p>
            </div>
            <div class="author-news author-news--page">
              @foreach($guides as $post)
                @include('content.partials.card', ['post' => $post])
              @endforeach
            </div>
            <div class="news-cta-wrap">
              <a class="news-cta" href="{{ rtrim(localized_url(null, 'content/guides'), '/') }}/">{{ __('content.explore_guides') }}</a>
            </div>
          </section>
        @endif
      @endif

      @include('content.partials.faq', ['faq' => $page->faqFor()])
    </div>
  </div>
@endsection
