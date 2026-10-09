<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'slots.tube')</title>
  <meta name="description" content="@yield('meta_description', 'Play free slots, read reviews and compare bonuses on slots.tube.')" />
  <meta name="theme-color" content="#17122b" />
  <meta name="robots" content="{{ app()->environment('staging', 'local') ? 'noindex, nofollow' : trim($__env->yieldContent('robots', 'index, follow')) }}">
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <link rel="icon" type="image/svg+xml" href="/assets/icons/logo-7.svg" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,700&family=Montserrat:wght@500;700;800&family=Roboto:wght@700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/css/styles.css?v=20261009-team1" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  @stack('head')
</head>
<body class="@yield('body_class')">
  @include('partials.header')

  @if(session('auth_error'))
    <div class="container" style="padding:12px 16px 0;">
      <p role="alert" style="margin:0;padding:12px 16px;border-radius:12px;background:#ffe8e0;color:#9a3412;font-weight:600;">{{ session('auth_error') }}</p>
    </div>
  @endif

  <main class="main" id="main">
    @yield('content')
  </main>

  @include('partials.footer')
  @include('partials.bottom-nav')
  @include('partials.auth-modal')

  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
  <script src="/js/main.js?v=20261008-icons1" defer></script>
  <script src="/js/auth-newsletter.js?v=20261005-hero" defer></script>
  @stack('scripts')
</body>
</html>
