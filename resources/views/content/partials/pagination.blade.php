@if($paginator->hasPages())
  @php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pageUrl = fn (int $page): string => $page === 1 ? $paginator->path() : $paginator->url($page);
    $pages = collect([1, $current - 1, $current, $current + 1, $last])
      ->filter(fn (int $page): bool => $page >= 1 && $page <= $last)
      ->unique()
      ->sort()
      ->values();
    $controls = [
      ['page' => 1, 'icon' => 'page-first', 'label' => __('content.pagination.first'), 'disabled' => $current === 1],
      ['page' => $current - 1, 'icon' => 'page-prev', 'label' => __('content.pagination.prev'), 'disabled' => $current === 1],
    ];
    $trailing = [
      ['page' => $current + 1, 'icon' => 'page-next', 'label' => __('content.pagination.next'), 'disabled' => $current === $last],
      ['page' => $last, 'icon' => 'page-last', 'label' => __('content.pagination.last'), 'disabled' => $current === $last],
    ];
  @endphp
  <nav class="catalog-pagination news-pagination" aria-label="{{ __('content.pagination.aria') }}">
    @foreach($controls as $control)
      @if($control['disabled'])
        <span class="catalog-pagination__btn catalog-pagination__btn--disabled" aria-hidden="true"><img src="/assets/images/free-slots/{{ $control['icon'] }}.svg" alt="" width="16" height="16" /></span>
      @else
        <a class="catalog-pagination__btn" href="{{ $pageUrl($control['page']) }}" aria-label="{{ $control['label'] }}"><img src="/assets/images/free-slots/{{ $control['icon'] }}.svg" alt="" width="16" height="16" /></a>
      @endif
    @endforeach

    @foreach($pages as $i => $page)
      @if($i > 0 && $page - $pages[$i - 1] > 1)
        <span class="catalog-pagination__ellipsis" aria-hidden="true">…</span>
      @endif
      @if($page === $current)
        <span class="catalog-pagination__btn catalog-pagination__btn--active" aria-current="page">{{ $page }}</span>
      @else
        <a class="catalog-pagination__btn" href="{{ $pageUrl($page) }}">{{ $page }}</a>
      @endif
    @endforeach

    @foreach($trailing as $control)
      @if($control['disabled'])
        <span class="catalog-pagination__btn catalog-pagination__btn--disabled" aria-hidden="true"><img src="/assets/images/free-slots/{{ $control['icon'] }}.svg" alt="" width="16" height="16" /></span>
      @else
        <a class="catalog-pagination__btn" href="{{ $pageUrl($control['page']) }}" aria-label="{{ $control['label'] }}"><img src="/assets/images/free-slots/{{ $control['icon'] }}.svg" alt="" width="16" height="16" /></a>
      @endif
    @endforeach
  </nav>
@endif
