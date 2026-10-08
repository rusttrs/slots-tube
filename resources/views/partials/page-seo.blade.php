{{-- Usage: @include('partials.page-seo', ['page' => PageSetting, 'title' => default, 'description' => default, 'canonical' => url, 'suffix' => optional]) --}}
@php
  $seoTitle = $page->metaTitle($title).($suffix ?? '');
  $seoDescription = $page->metaDescription($description);
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@if($page->noindex || ($noindex ?? false))
  @section('robots', 'noindex, follow')
@endif

@push('head')
  <link rel="canonical" href="{{ $canonical }}" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="slots.tube" />
  <meta property="og:title" content="{{ $seoTitle }}" />
  <meta property="og:description" content="{{ $seoDescription }}" />
  <meta property="og:url" content="{{ $canonical }}" />
@endpush
