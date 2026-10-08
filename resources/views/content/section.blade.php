@extends('layouts.app')

@php
  $label = __('content.sections.'.$type);
  $pageSuffix = $posts->currentPage() > 1 ? ' — '.__('content.page', ['page' => $posts->currentPage()]) : '';
@endphp

@include('partials.page-seo', [
  'page' => $page,
  'title' => __('content.section_meta_title', ['section' => $label]),
  'description' => __('content.section_texts.'.$type),
  'canonical' => $canonical,
  'suffix' => $pageSuffix,
])

@push('head')
  @if($posts->previousPageUrl())
    <link rel="prev" href="{{ $posts->currentPage() === 2 ? $posts->path() : $posts->previousPageUrl() }}" />
  @endif
  @if($posts->nextPageUrl())
    <link rel="next" href="{{ $posts->nextPageUrl() }}" />
  @endif
@endpush

@section('content')
  <div class="main--news">
    <div class="container">
      <header class="page-intro page-intro--center page-intro--news">
        <h1 class="page-intro__title page-intro__title--news">{{ __('content.title') }} <span class="page-intro__accent">{{ $label }}</span></h1>
        <p class="page-intro__subtitle">{{ __('content.section_texts.'.$type) }}</p>
      </header>

      <section class="news-panel news-section" aria-labelledby="news-section-title">
        <div class="news-section-head">
          <h2 class="news-section-head__title" id="news-section-title">{{ __('content.section_heading', ['section' => $label]) }}</h2>
          <p class="news-section-head__text">{{ trans_choice('content.section_count', $posts->total(), ['count' => $posts->total()]) }}</p>
        </div>

        <nav class="category-mosaic category-mosaic--news" aria-label="{{ __('content.sections_aria') }}">
          <div class="category-mosaic__grid">
            <a class="category-mosaic__pill" href="{{ rtrim(localized_url(null, 'content'), '/') }}/"><span class="category-mosaic__label">{{ __('content.all_sections') }}</span></a>
            @foreach($sections as $section)
              <a class="category-mosaic__pill {{ $section['type'] === $type ? 'category-mosaic__pill--active' : '' }}" href="{{ rtrim($section['url'], '/') }}/" @if($section['type'] === $type) aria-current="page" @endif><img class="category-mosaic__icon" src="{{ $section['icon'] }}" alt="" width="22" height="22" /><span class="category-mosaic__label">{{ $section['label'] }}</span></a>
            @endforeach
          </div>
        </nav>

        @if($posts->isEmpty())
          <p class="news-section-head__text news-empty">{{ __('content.empty') }}</p>
        @else
          <div class="author-news author-news--page">
            @foreach($posts as $post)
              @include('content.partials.card', ['post' => $post])
            @endforeach
          </div>

          @include('content.partials.pagination', ['paginator' => $posts])
        @endif
      </section>

      @if($posts->onFirstPage())
        @include('content.partials.faq', ['faq' => $page->faqFor()])
      @endif
    </div>
  </div>
@endsection
