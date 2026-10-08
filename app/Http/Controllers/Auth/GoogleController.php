<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(Request $request)
    {
        remember_auth_return($request->string('return_to')->toString());

        if (! config('services.google.client_id')) {
            return redirect()->to(localized_url(null, ''))
                ->with('auth_error', 'Google login is not configured yet.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        if (! config('services.google.client_id')) {
            return redirect()->to(localized_url(null, ''))
                ->with('auth_error', 'Google login is not configured yet.');
        }

        $googleUser = Socialite::driver('google')->user();

        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if (! $user && $googleUser->getEmail()) {
            $user = User::query()->where('email', strtolower($googleUser->getEmail()))->first();
        }

        if (! $user) {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'User'),
                'email' => strtolower((string) $googleUser->getEmail()),
                'google_id' => $googleUser->getId(),
                'avatar_path' => $googleUser->getAvatar(),
                'password' => null,
                'email_verified_at' => now(),
                'age_confirmed' => true,
            ]);
        } else {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar_path' => $user->avatar_path ?: $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        if (! $user->isActive()) {
            return redirect()->to(localized_url(null, ''))
                ->with('auth_error', 'Your account has been deactivated.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if ($user->needsOnboarding()) {
            return redirect()->to(localized_url(null, 'profile'));
        }

        return redirect()->to(pull_auth_return() ?: localized_url(null, ''));
    }
}
