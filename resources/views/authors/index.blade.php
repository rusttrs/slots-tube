@extends('layouts.app')

@php
  $locale = app()->getLocale();
  $title = $page->text('title');
  $lead = $page->text('lead');
  $joinLabel = $page->text('join_label');
  $joinUrl = $page->text('join_url');
  $joinUrl = preg_match('#^(https?://|mailto:|/|\#)#i', $joinUrl) === 1 ? $joinUrl : '';
  $sections = array_values(array_filter($sections, fn (array $section): bool => $section['authors']->isNotEmpty() || ($section['show_join'] && $joinLabel !== '')));
@endphp

@include('partials.page-seo', [
  'page' => $page,
  'title' => __('author.team_page.meta_title'),
  'description' => \Illuminate\Support\Str::limit($lead, 155),
  'canonical' => $canonical,
])

@push('head')
  <script type="application/ld+json">{!! \App\Support\AuthorSchema::json(\App\Support\AuthorSchema::team($canonical, $title, $page->metaDescription(\Illuminate\Support\Str::limit($lead, 155)), $sections)) !!}</script>
@endpush

@section('content')
  <div class="main--authors">
    <div class="container">
      <header class="page-intro page-intro--authors">
        <nav class="breadcrumb" aria-label="{{ __('slot.breadcrumb') }}">
          <ol class="breadcrumb__list">
            <li class="breadcrumb__item">
              <a class="breadcrumb__link breadcrumb__link--home" href="{{ localized_url($locale, '/') }}">
                <x-site-icon name="author-home" width="24" height="24" />
                <span>{{ __('slot.home') }}</span>
              </a>
            </li>
            <li class="breadcrumb__item" aria-hidden="true"><x-site-icon name="author-chevron" class="breadcrumb__sep" width="16" height="16" /></li>
            <li class="breadcrumb__item"><span class="breadcrumb__link">{{ __('author.team_page.about') }}</span></li>
            <li class="breadcrumb__item" aria-hidden="true"><x-site-icon name="author-chevron" class="breadcrumb__sep" width="16" height="16" /></li>
            <li class="breadcrumb__item"><span class="breadcrumb__current" aria-current="page">{{ __('author.team') }}</span></li>
          </ol>
        </nav>
        <h1 class="page-intro__title page-intro__title--authors">{{ $title }}</h1>
        @if($lead !== '')
          <p class="page-intro__lead page-intro__lead--authors">{{ $lead }}</p>
        @endif
      </header>

      @foreach($sections as $index => $section)
        @if($section['title'] !== '' || $section['text'] !== '')
          <section class="authors-note" @if($section['title'] !== '') aria-labelledby="authors-group-{{ $index }}-title" @endif>
            @if($section['title'] !== '')
              <h2 class="authors-note__title" id="authors-group-{{ $index }}-title">{{ $section['title'] }}</h2>
            @endif
            @if($section['text'] !== '')
              <p class="authors-note__text">{{ $section['text'] }}</p>
            @endif
          </section>
        @endif

        <div class="authors-grid">
          @foreach($section['authors'] as $author)
            @php($position = $author->positionLabel())
            <a class="author-card" href="{{ rtrim($author->publicUrl(), '/') }}/">
              <span class="author-card__media"><img src="{{ $author->avatarUrl() ?: asset('assets/images/avatar.png') }}" alt="{{ $author->displayName() }}" width="254" height="254" loading="lazy" /></span>
              <span class="author-card__body">
                <img class="author-card__logo" src="/assets/images/authors/minilogo.svg" alt="" width="49" height="34" />
                <span class="author-card__meta">
                  <span class="author-card__name">{{ $author->displayName() }}</span>
                  @if($position)
                    <span class="author-card__role">{{ $position }}</span>
                  @endif
                </span>
              </span>
            </a>
          @endforeach

          @if($section['show_join'] && $joinLabel !== '')
            @php($joinTag = $joinUrl !== '' ? 'a' : 'div')
            <{{ $joinTag }} class="author-card author-card--join @if($joinTag === 'div') author-card--static @endif" @if($joinTag === 'a') href="{{ $joinUrl }}" @endif>
              <span class="author-card__media author-card__media--join" aria-hidden="true"><span class="author-card__q">?</span></span>
              <span class="author-card__body">
                <img class="author-card__logo" src="/assets/images/authors/minilogo.svg" alt="" width="49" height="34" />
                <span class="author-card__meta">
                  <span class="author-card__role author-card__role--join">{{ $joinLabel }}</span>
                </span>
              </span>
            </{{ $joinTag }}>
          @endif
        </div>
      @endforeach

      @include('content.partials.faq', ['faq' => $page->faqFor()])
    </div>
  </div>
@endsection
