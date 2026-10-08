@props(['name'])
<svg {{ $attributes->class(['icon'])->merge(['aria-hidden' => 'true', 'focusable' => 'false']) }}><use href="{{ \App\Support\IconSprite::url($name) }}"></use></svg>