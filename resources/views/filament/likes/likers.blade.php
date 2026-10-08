@if($likes->isEmpty())
  <p style="color:#6b7280;font-size:14px;">Пока никто не лайкнул.</p>
@else
  <p style="color:#6b7280;font-size:13px;margin-bottom:8px;">Всего лайков: {{ $total }}@if($total > $likes->count()) · показаны последние {{ $likes->count() }}@endif</p>
  <ul style="display:flex;flex-direction:column;gap:10px;">
    @foreach($likes as $like)
      <li style="display:flex;align-items:center;gap:10px;">
        <img src="{{ $like->user?->avatarUrl() }}" alt="" style="width:32px;height:32px;border-radius:8px;object-fit:cover;flex:none;" />
        <span style="flex:1;min-width:0;">
          <span style="display:block;font-weight:600;font-size:14px;">{{ $like->user?->displayName() ?: '—' }}</span>
          <span style="display:block;color:#6b7280;font-size:12px;">{{ $like->user?->email }}</span>
        </span>
        <span style="color:#6b7280;font-size:12px;white-space:nowrap;">{{ $like->created_at?->format('d.m.Y H:i') }}</span>
      </li>
    @endforeach
  </ul>
@endif
