{{-- Usage: @include('partials.page-about', ['blocks' => $page->blocksFor()]) — sections from App\Support\PageAboutBlocks --}}
@php
  $inline = fn (string $text): string => str_replace('<a ', '<a class="page-about__link" ', trim(\Illuminate\Support\Str::inlineMarkdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false])));
  $paragraphs = fn (string $text): array => array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $text)), fn (string $p): bool => $p !== ''));
@endphp

@if($blocks !== [])
  <div class="page-about-stack">
    @foreach($blocks as $section)
      @php
        $titleId = 'page-about-'.$loop->iteration.'-title';
        $guideNumber = 0;
        $classes = array_filter([
          'page-about',
          $section['hero'] !== '' ? 'page-about--games' : null,
          $section['pills'] !== [] ? 'page-about--games-trusted' : null,
          $section['guides'] ? 'page-about--guides' : null,
        ]);
      @endphp
      <section class="{{ implode(' ', $classes) }}" @if($section['title'] !== '') aria-labelledby="{{ $titleId }}" @endif>
        @if($section['hero'] !== '')
          <div class="page-about__hero">
            <img src="{{ media_url($section['hero']) }}" alt="" width="1180" height="202" loading="lazy" />
          </div>
        @endif
        @if($section['pills'] !== [])
          <div class="page-about__pills">
            @foreach($section['pills'] as $pill)
              <span class="page-about__pill">{{ $pill }}</span>
            @endforeach
          </div>
        @endif
        @if($section['title'] !== '')
          <h2 class="page-about__title" id="{{ $titleId }}">{{ $section['title'] }}</h2>
        @endif

        @foreach($section['elements'] as $element)
          @switch($element['type'])
            @case('heading')
              @if($element['level'] === 'h2')
                <h2 class="page-about__title">{{ $element['text'] }}</h2>
              @else
                <h3 class="page-about__subtitle"><span class="page-about__bar" aria-hidden="true"></span>{{ $element['text'] }}</h3>
              @endif
              @break

            @case('text')
              @foreach($paragraphs($element['text']) as $paragraph)
                <p class="{{ $element['lead'] ? 'page-about__lead' : 'page-about__text' }}">{!! $inline($paragraph) !!}</p>
              @endforeach
              @break

            @case('rule')
              <hr class="page-about__rule" />
              @break

            @case('image')
              @if($element['style'] === 'promo')
                <div class="page-about__promo">
                  <img class="page-about__promo-img" src="{{ media_url($element['image']) }}" alt="{{ $element['alt'] }}" width="1179" height="202" loading="lazy" />
                </div>
              @else
                <div class="page-about__banner">
                  <img src="{{ media_url($element['image']) }}" alt="{{ $element['alt'] }}" width="1119" height="201" loading="lazy" />
                </div>
              @endif
              @break

            @case('cards')
              <div class="page-about__grid page-about__grid--{{ $element['columns'] }}">
                @foreach($element['cards'] as $card)
                  <article class="page-about__card page-about__card--{{ $element['style'] }}">
                    <h3 class="page-about__card-title {{ $element['style'] === 'outline' ? 'page-about__card-title--accent' : '' }}"><span class="page-about__bar" aria-hidden="true"></span>{{ $card['title'] }}</h3>
                    @foreach($paragraphs($card['text']) as $paragraph)
                      <p>{!! $inline($paragraph) !!}</p>
                    @endforeach
                    @if($card['items'] !== [])
                      <ul class="page-about__list">
                        @foreach($card['items'] as $item)
                          <li>{!! $inline($item) !!}</li>
                        @endforeach
                      </ul>
                    @endif
                  </article>
                @endforeach
              </div>
              @break

            @case('checks')
              <ul class="page-about__checks {{ $element['compact'] ? 'page-about__checks--standout' : '' }}">
                @foreach($element['items'] as $item)
                  <li><x-site-icon name="page-about-check" width="14" height="10" /><span class="page-about__checks-text">{!! $inline($item) !!}</span></li>
                @endforeach
              </ul>
              @break

            @case('steps')
              <ol class="page-about__steps">
                @foreach($element['items'] as $item)
                  <li><span class="page-about__step-num" aria-hidden="true">{{ $loop->iteration }}</span><span>{!! $inline($item) !!}</span></li>
                @endforeach
              </ol>
              @break

            @case('tip')
              <aside class="page-about__tip">
                <div class="page-about__tip-head">
                  <span class="page-about__tip-icon" aria-hidden="true"><x-site-icon name="page-about-tip" width="15" height="15" /></span>
                  <p class="page-about__tip-title">{{ $element['title'] }}</p>
                </div>
                @if($element['text'] !== '')
                  <p class="page-about__tip-text">{!! $inline($element['text']) !!}</p>
                @endif
              </aside>
              @break

            @case('feature')
              <a class="about-feature" href="{{ $element['url'] ?: '#' }}">
                @if($element['image'] !== '')
                  <img class="about-feature__img" src="{{ media_url($element['image']) }}" alt="" width="180" height="90" loading="lazy" />
                @endif
                <span class="about-feature__body">
                  <span class="about-feature__title">{{ $element['title'] }}</span>
                  @if($element['meta'] !== '')
                    <span class="about-feature__date">{{ $element['meta'] }}</span>
                  @endif
                </span>
                <span class="about-feature__more" aria-hidden="true"><x-site-icon name="page-about-chevron" width="20" height="20" /></span>
              </a>
              @break

            @case('articles')
              <div class="about-articles">
                @foreach($element['items'] as $article)
                  <a class="about-article" href="{{ $article['url'] ?: '#' }}">
                    <span class="about-article__media">
                      @if($article['image'] !== '')
                        <img src="{{ media_url($article['image']) }}" alt="" width="200" height="100" loading="lazy" />
                      @endif
                      @if($article['label'] !== '')
                        <span class="about-article__label"><x-site-icon name="page-about-guide-label" width="15" height="15" />{{ $article['label'] }}</span>
                      @endif
                    </span>
                    <span class="about-article__body">
                      <span class="about-article__title">{{ $article['title'] }}</span>
                      @if($article['meta'] !== '')
                        <span class="about-article__date">{{ $article['meta'] }}</span>
                      @endif
                    </span>
                    <span class="about-article__btn">{{ $article['button'] ?: __('content.read') }} <x-site-icon name="page-about-chevron" width="20" height="20" /></span>
                  </a>
                @endforeach
              </div>
              @break

            @case('guides')
              @php($guideNumber++)
              <div class="page-about__guide-group">
                <span class="page-about__guide-num">{{ str_pad((string) $guideNumber, 2, '0', STR_PAD_LEFT) }}</span>
                <div class="page-about__guide-body">
                  @if($element['heading'] !== '')
                    <h3 class="page-about__guide-heading">{{ $element['heading'] }}</h3>
                  @endif
                  @if($element['text'] !== '')
                    <p class="page-about__guide-text">{!! $inline($element['text']) !!}</p>
                  @endif
                  @if($element['cards'] !== [])
                    <div class="page-about__guide-grid {{ $element['columns'] === 2 ? 'page-about__guide-grid--2' : '' }}">
                      @foreach($element['cards'] as $card)
                        <a class="page-about__guide-card" href="{{ $card['url'] ?: '#' }}">
                          <span class="page-about__guide-media">
                            @if($card['image'] !== '')
                              <img src="{{ media_url($card['image']) }}" alt="" loading="lazy" />
                            @endif
                          </span>
                          <span class="page-about__guide-title">{{ $card['title'] }}</span>
                        </a>
                      @endforeach
                    </div>
                  @endif
                </div>
              </div>
              @break

            @case('guides_link')
              <a class="page-about__guides-all" href="{{ $element['url'] ?: '#' }}">{{ $element['label'] }} <x-site-icon name="page-about-arrow" width="14" height="14" /></a>
              @break
          @endswitch
        @endforeach
      </section>
    @endforeach
  </div>
@endif
