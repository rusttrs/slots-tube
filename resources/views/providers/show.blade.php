@php
  $name = $provider->displayName();
@endphp

@extends('layouts.app')

@section('title', $name.' | slots.tube')
@section('meta_description', 'Play free '.$name.' slots and compare games on slots.tube.')

@section('content')
  <div class="container">
    <section class="provider-page" aria-labelledby="provider-page-title">
      <p class="provider-page__tag">
        <svg class="icon" aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 16.8833 16.875" preserveAspectRatio="xMidYMid meet"><use href="#provider"></use></svg>
        <span>Provider</span>
      </p>
      <h1 id="provider-page-title" class="provider-page__title">{{ $name }}</h1>
      <p class="provider-page__count">All Games [ {{ $slots->count() }} ]</p>

      @if($slots->isNotEmpty())
        <div class="provider-page__grid">
          @foreach($slots as $slot)
            <a class="related-game" href="{{ rtrim($slot->publicUrl(), '/').'/' }}">
              @if($slot->aboutCoverUrl())
                <img
                  src="{{ $slot->aboutCoverUrl() }}"
                  alt="{{ $slot->displayTitle() }}"
                  width="172"
                  height="224"
                  loading="lazy"
                />
              @endif
              <span class="related-game__name">{{ $slot->displayTitle() }}</span>
            </a>
          @endforeach
        </div>
      @else
        <p class="provider-page__empty">No published games from this provider yet.</p>
      @endif
    </section>
  </div>
@endsection
