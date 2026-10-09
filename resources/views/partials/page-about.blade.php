{{-- Usage: @include('partials.page-about', ['blocks' => $page->blocksFor()]) --}}
@php
  $inline = fn (string $text): string => str_replace('<a ', '<a class="page-about__link" ', trim(\Illuminate\Support\Str::inlineMarkdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false])));
  $paragraphs = fn (string $text): array => array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $text)), fn (string $p): bool => $p !== ''));
@endphp

@if($blocks !== [])
  <div class="page-about-stack">
    @foreach($blocks as $block)
      @php($titleId = 'page-about-'.$loop->iteration.'-title')
      <section class="page-about" @if($block['title'] !== '') aria-labelledby="{{ $titleId }}" @endif>
        @if($block['title'] !== '')
          <h2 class="page-about__title" id="{{ $titleId }}">{{ $block['title'] }}</h2>
        @endif

        @foreach($paragraphs($block['text']) as $paragraph)
          <p class="{{ $block['type'] === 'cards' ? 'page-about__lead' : 'page-about__text' }}">{!! $inline($paragraph) !!}</p>
        @endforeach

        @if($block['type'] === 'cards' && $block['cards'] !== [])
          <div class="page-about__grid page-about__grid--{{ $block['columns'] }}">
            @foreach($block['cards'] as $card)
              <article class="page-about__card page-about__card--{{ $block['style'] }}">
                <h3 class="page-about__card-title {{ $block['style'] === 'outline' ? 'page-about__card-title--accent' : '' }}"><span class="page-about__bar" aria-hidden="true"></span>{{ $card['title'] }}</h3>
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
        @endif

        @if($block['type'] === 'text' && $block['items'] !== [])
          <ul class="page-about__checks {{ $block['text'] !== '' ? 'page-about__checks--standout' : '' }}">
            @foreach($block['items'] as $item)
              <li><x-site-icon name="page-about-check" width="14" height="10" /><span class="page-about__checks-text">{!! $inline($item) !!}</span></li>
            @endforeach
          </ul>
        @endif

        @if($block['type'] === 'steps' && $block['items'] !== [])
          <ol class="page-about__steps">
            @foreach($block['items'] as $item)
              <li><span class="page-about__step-num" aria-hidden="true">{{ $loop->iteration }}</span><span>{!! $inline($item) !!}</span></li>
            @endforeach
          </ol>
        @endif

        @if($block['type'] === 'steps' && $block['tip_title'] !== '')
          <aside class="page-about__tip">
            <div class="page-about__tip-head">
              <span class="page-about__tip-icon" aria-hidden="true"><x-site-icon name="page-about-tip" width="15" height="15" /></span>
              <p class="page-about__tip-title">{{ $block['tip_title'] }}</p>
            </div>
            @if($block['tip_text'] !== '')
              <p class="page-about__tip-text">{!! $inline($block['tip_text']) !!}</p>
            @endif
          </aside>
        @endif

        @if($block['closing'] !== '')
          @foreach($paragraphs($block['closing']) as $paragraph)
            <p class="page-about__text">{!! $inline($paragraph) !!}</p>
          @endforeach
        @endif
      </section>
    @endforeach
  </div>
@endif
