@if($query === '')
  @if($recommended->isNotEmpty())
    <section class="site-search__group">
      <div class="site-search__bar">
        <h2 class="site-search__bar-title">{{ __('search.recommended') }}</h2>
        <span class="site-search__bar-count">{{ __('search.recommended_count', ['count' => $recommended->count()]) }}</span>
      </div>
      <div class="site-search__bonuses">
        @foreach($recommended as $bonus)
          @include('partials.casino-card', ['bonus' => $bonus, 'cta' => __('search.go')])
        @endforeach
      </div>
    </section>
  @endif
@else
  @forelse($groups as $key => $group)
    <section class="site-search__group">
      <div class="site-search__bar">
        <h2 class="site-search__bar-title">{{ $group['label'] }}</h2>
        <span class="site-search__bar-count">{{ __('search.found', ['count' => $group['total']]) }}</span>
      </div>
      @if($key === 'bonuses')
        <div class="site-search__bonuses">
          @foreach($group['bonuses'] as $bonus)
            @include('partials.casino-card', ['bonus' => $bonus, 'cta' => __('search.go')])
          @endforeach
        </div>
      @else
        <ul class="site-search__list">
          @foreach($group['items'] as $item)
            <li>
              <a class="site-search__item" href="{{ $item['url'] }}">
                @if($item['image'])
                  <img class="site-search__thumb" src="{{ $item['image'] }}" alt="" width="48" height="48" loading="lazy" />
                @else
                  <span class="site-search__thumb site-search__thumb--empty" aria-hidden="true"><x-site-icon :name="$item['icon']" width="18" height="18" /></span>
                @endif
                <span class="site-search__item-text">
                  <span class="site-search__item-title">{{ $item['title'] }}</span>
                  <span class="site-search__item-meta">{{ $item['meta'] }}</span>
                </span>
              </a>
            </li>
          @endforeach
        </ul>
      @endif
    </section>
  @empty
    <p class="site-search__empty">{{ __('search.empty', ['query' => $query]) }}</p>
  @endforelse
@endif
