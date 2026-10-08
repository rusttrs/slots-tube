@extends('layouts.app')

@section('title', $title ?? 'slots.tube')

@section('content')
  <section class="container" style="padding: 48px 16px 80px; text-align: center;">
    <h1 style="font-family: Montserrat, sans-serif; font-size: clamp(28px, 4vw, 42px); margin: 0 0 12px;">{{ $heading }}</h1>
    <p style="max-width: 520px; margin: 0 auto; color: #6c6762;">{{ $text }}</p>
  </section>
@endsection
