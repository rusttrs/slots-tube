@php
  $url = rtrim($post->publicUrl(), '/').'/';
  $title = $post->displayTitle();
  $excerpt = \Illuminate\Support\Str::limit(trim(strip_tags($post->displayExcerpt())), 150);
  $cover = $post->coverUrl() ?: asset('assets/images/author/news-1.png');
  $date = $post->created_at;
  $authorName = $post->author?->displayName();
@endphp
<article class="author-news-card">
  <a class="author-news-card__media" href="{{ $url }}" tabindex="-1" aria-hidden="true">
    <img src="{{ $cover }}" alt="" width="365" height="183" loading="lazy" />
    <span class="author-news-card__label"><img src="/assets/images/author/icon-news.svg" alt="" width="19" height="19" />{{ __('content.sections.'.$post->type) }}</span>
    <img class="author-news-card__logo" src="/assets/images/author/news-mini-logo.svg" alt="" width="131" height="24" />
  </a>
  <div class="author-news-card__body">
    <h3 class="author-news-card__title"><a href="{{ $url }}">{{ $title }}</a></h3>
    <div class="author-news-card__meta">
      @if($date)
        <time class="author-news-card__date" datetime="{{ $date->toDateString() }}"><img src="/assets/images/author/icon-calendar.svg" alt="" width="15" height="15" />{{ $date->translatedFormat('F j, Y') }}</time>
      @endif
      @if($authorName && $post->author->is_published)
        <a class="author-news-card__by" href="{{ rtrim($post->author->publicUrl(), '/').'/' }}"><img src="/assets/images/author/icon-author.svg" alt="" width="15" height="15" />{{ $authorName }}</a>
      @elseif($authorName)
        <span class="author-news-card__by"><img src="/assets/images/author/icon-author.svg" alt="" width="15" height="15" />{{ $authorName }}</span>
      @endif
    </div>
    @if($excerpt !== '')
      <p class="author-news-card__excerpt">{{ $excerpt }}</p>
    @endif
  </div>
</article>
