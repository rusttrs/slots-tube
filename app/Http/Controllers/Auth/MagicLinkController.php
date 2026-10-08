<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class MagicLinkController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'intent' => ['nullable', 'in:login,signup'],
            'age' => ['nullable', 'boolean'],
        ]);

        $intent = $data['intent'] ?? 'login';
        $email = strtolower($data['email']);

        if ($intent === 'signup' && empty($data['age'])) {
            throw ValidationException::withMessages([
                'age' => 'Please confirm that you are over 18 years old!',
            ]);
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => strstr($email, '@', true) ?: 'User',
                'password' => null,
                'age_confirmed' => $intent === 'signup',
                'newsletter_opt_in' => $intent === 'signup',
            ]
        );

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated.',
            ]);
        }

        if ($intent === 'signup') {
            $user->forceFill([
                'age_confirmed' => true,
                'newsletter_opt_in' => true,
            ])->save();
        }

        remember_auth_return();

        $url = URL::temporarySignedRoute(
            'auth.magic.verify',
            now()->addMinutes(30),
            ['user' => $user->id]
        );

        Mail::to($user->email)->send(new MagicLinkMail($user, $url, $intent));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'email' => $user->email,
                'message' => 'Magic link sent',
            ]);
        }

        return back()->with('auth_check_email', $user->email);
    }

    public function verify(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired magic link.');
        }

        if (! $user->isActive()) {
            return redirect()->to(localized_url(null, ''))
                ->with('auth_error', 'Your account has been deactivated.');
        }

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if ($user->needsOnboarding()) {
            return redirect()->to(localized_url(null, 'profile'));
        }

        return redirect()->to(pull_auth_return() ?: localized_url(null, ''));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(localized_url(null, ''));
    }
}
