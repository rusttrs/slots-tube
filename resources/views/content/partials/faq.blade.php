@if($faq !== [])
  <section class="news-panel news-faq" id="faq" aria-labelledby="news-faq-title">
    <div class="news-section-head">
      <h2 class="news-section-head__title" id="news-faq-title">{{ __('content.faq_title') }}</h2>
    </div>
    <div class="faq__list">
      @foreach($faq as $item)
        <details class="faq__item">
          <summary class="faq__summary">
            <span class="faq__question">{{ $item['question'] }}</span>
            <svg class="icon faq__chevron" width="16" height="16" viewBox="0 0 16 16"><use href="#faq-chevron"></use></svg>
          </summary>
          <div class="faq__panel">
            <p class="faq__answer">{{ $item['answer'] }}</p>
          </div>
        </details>
      @endforeach
    </div>
  </section>

  @push('head')
    <script type="application/ld+json">{!! json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'mainEntity' => array_map(fn (array $item): array => [
        '@type' => 'Question',
        'name' => $item['question'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
      ], $faq),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
  @endpush
@endif
