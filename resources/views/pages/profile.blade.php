@extends('layouts.app')

@section('title', 'Profile | slots.tube')

@section('content')
  <section class="container" style="padding: 48px 16px 80px; max-width: 480px; margin: 0 auto;">
    <h1 style="font-family: Montserrat, sans-serif; margin: 0 0 24px;">Profile Settings</h1>
    @if(session('profile_saved'))
      <p style="color: #0a7a3e; margin-bottom: 16px;">Saved.</p>
    @endif
    <form method="post" action="{{ localized_url(null, 'profile') }}" enctype="multipart/form-data">
      @csrf
      <label style="display:block;margin-bottom:12px;">
        <span style="display:block;margin-bottom:6px;">Nickname</span>
        <input type="text" name="nickname" value="{{ old('nickname', $user->nickname ?: $user->displayName()) }}" required style="width:100%;padding:12px;border:1px solid #dedee0;border-radius:12px;" />
        @error('nickname')<span style="color:#c00;">{{ $message }}</span>@enderror
      </label>
      <label style="display:block;margin-bottom:12px;">
        <span style="display:block;margin-bottom:6px;">Avatar</span>
        <input type="file" name="avatar" accept="image/*" />
        @error('avatar')<span style="display:block;color:#c00;">{{ $message }}</span>@enderror
      </label>
      <button type="submit" style="margin-top:12px;padding:12px 20px;border:0;border-radius:20px;background:#ff6421;color:#fff;font-weight:700;">Save</button>
    </form>
  </section>
@endsection
