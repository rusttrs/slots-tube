<div class="site-search" id="site-search" role="dialog" aria-modal="true" aria-label="{{ __('search.label') }}" data-endpoint="{{ localized_url(null, 'search') }}" hidden>
  <div class="site-search__backdrop" data-search-close></div>
  <div class="site-search__panel">
    <form class="site-search__form" role="search" action="{{ localized_url(null, 'free-slots') }}" method="get">
      <x-site-icon name="search" class="site-search__form-icon" width="20" height="20" />
      <input class="site-search__input" type="search" name="q" placeholder="{{ __('search.placeholder') }}" aria-label="{{ __('search.label') }}" autocomplete="off" enterkeyhint="search" />
    </form>
    <div class="site-search__tags" role="group" aria-label="{{ __('search.filters') }}">
      @foreach(\App\Http\Controllers\SearchController::TYPES as $type)
        <button class="site-search__tag {{ $type === 'all' ? 'is-active' : '' }}" type="button" data-search-type="{{ $type }}" aria-pressed="{{ $type === 'all' ? 'true' : 'false' }}">{{ __('search.tags.'.$type) }}</button>
      @endforeach
    </div>
    <div class="site-search__results" data-search-results aria-live="polite"></div>
  </div>
</div>
