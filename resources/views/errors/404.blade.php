<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('errors.404.meta_title') }}</title>
  <meta name="description" content="{{ __('errors.404.meta_description') }}" />
  <meta name="robots" content="noindex" />
  <meta name="theme-color" content="#17122b" />
  <link rel="icon" type="image/svg+xml" href="/assets/icons/logo-7.svg" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/css/styles.css?v=20261009-bonuses1" />
</head>
<body class="page-404">
  <header class="page-404__header">
    <a class="logo" href="{{ localized_url(null, '') }}" aria-label="Slots.tube home">
      <img class="logo__icon" src="/assets/images/header/1a6dc.svg" alt="" width="43" height="30" />
      <span class="logo__text">slots.tube</span>
    </a>
  </header>

  <main class="page-404__main">
    <div class="page-404__visual" aria-hidden="true">
      <img class="page-404__digits" src="/assets/images/404/digits.png" alt="" width="520" height="179" />
      <img class="page-404__cable" src="/assets/images/404/cable-plug.png" alt="" width="1125" height="82" />
    </div>

    <h1 class="page-404__title">{{ __('errors.404.title') }}</h1>
    <p class="page-404__text">
      {{ __('errors.404.text_line1') }}<br />
      {{ __('errors.404.text_line2') }}
    </p>

    <a class="page-404__btn" href="{{ localized_url(null, '') }}">{{ __('errors.404.button') }}</a>
  </main>
</body>
</html>
