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
  <a class="casino-card__btn" href="{{ $bonus->cta_url ?: '#' }}" rel="sponsored nofollow" @if($bonus->cta_url) target="_blank" @endif>{{ $cta ?? __('slot.play_now') }}</a>
  <p class="casino-card__terms">{{ __('slot.casino_terms') }}</p>
</article>
