<?php

namespace App\Http\Controllers;

use App\Mail\EmailChangeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show()
    {
        return view('pages.profile', [
            'user' => Auth::user(),
        ]);
    }

    /** Avatar modal (also the onboarding step after the first login). */
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validateWithBag('avatar', [
            'nickname' => ['required', 'string', 'min:2', 'max:40'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:300'],
            'avatar_preset' => ['nullable', 'string', Rule::in(User::AVATAR_PRESETS)],
        ]);

        $this->applyNickname($user, $data['nickname'], 'avatar');

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'r2');
            if (! is_string($path)) {
                throw ValidationException::withMessages(['avatar' => __('profile.upload_failed')])->errorBag('avatar');
            }
            $user->avatar_path = $path;
        } elseif (filled($data['avatar_preset'] ?? null)) {
            $user->avatar_path = $data['avatar_preset'];
        }

        $this->completeOnboarding($user);
        $user->save();

        return redirect()->to(localized_url(null, 'profile'))->with('profile_saved', 'avatar');
    }

    public function updateUsername(Request $request)
    {
        $user = $request->user();

        $data = $request->validateWithBag('username', [
            'nickname' => ['required', 'string', 'min:2', 'max:40'],
        ]);

        $this->applyNickname($user, $data['nickname'], 'username');
        $this->completeOnboarding($user);
        $user->save();

        return redirect()->to(localized_url(null, 'profile'))->with('profile_saved', 'username');
    }

    public function requestEmailChange(Request $request)
    {
        $user = $request->user();

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'email_confirmation' => strtolower(trim((string) $request->input('email_confirmation'))),
        ]);

        $data = $request->validateWithBag('email', [
            'email' => ['required', 'string', 'email', 'max:255', 'confirmed'],
        ]);

        $email = $data['email'];

        if ($email === strtolower((string) $user->email)) {
            throw ValidationException::withMessages(['email' => __('profile.email_same')])->errorBag('email');
        }

        if ($this->emailTaken($email, $user)) {
            throw ValidationException::withMessages(['email' => __('profile.email_taken')])->errorBag('email');
        }

        $url = URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), [
            'user' => $user->id,
            'email' => $email,
            'from' => $this->emailFingerprint($user),
            'lang' => app()->getLocale(),
        ]);

        Mail::to($email)->send(new EmailChangeMail($user, $email, $url));

        return redirect()->to(localized_url(null, 'profile'))->with('email_change_sent', $email);
    }

    public function confirmEmailChange(Request $request, User $user)
    {
        $email = strtolower((string) $request->query('email'));
        $lang = (string) $request->query('lang');
        $home = localized_url($lang, '');

        abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 403);
        abort_if(Auth::check() && (int) Auth::id() !== (int) $user->id, 403);

        if (! $user->isActive()) {
            return redirect()->to($home)->with('auth_error', __('profile.account_deactivated', [], $lang ?: null));
        }

        if ($email === strtolower((string) $user->email)) {
            return redirect()->to(Auth::check() ? localized_url($lang, 'profile') : $home);
        }

        if (! hash_equals($this->emailFingerprint($user), (string) $request->query('from')) || $this->emailTaken($email, $user)) {
            return redirect()->to($home)->with('auth_error', __('profile.email_link_invalid', [], $lang ?: null));
        }

        $user->forceFill(['email' => $email, 'email_verified_at' => now()])->save();

        if (! Auth::check()) {
            Auth::login($user, remember: true);
            $request->session()->regenerate();
        }

        return redirect()->to(localized_url($lang, 'profile'))->with('email_changed', true);
    }

    private function applyNickname(User $user, string $nickname, string $errorBag): void
    {
        $nickname = ltrim(trim($nickname), '@');

        if ($nickname === (string) $user->nickname) {
            return;
        }

        if (mb_strlen($nickname) < 2) {
            throw ValidationException::withMessages(['nickname' => __('validation.min.string', ['attribute' => 'nickname', 'min' => 2])])->errorBag($errorBag);
        }

        if ($availableAt = $user->nicknameChangeAvailableAt()) {
            throw ValidationException::withMessages([
                'nickname' => __('profile.username_locked', ['date' => $availableAt->translatedFormat('F j, Y')]),
            ])->errorBag($errorBag);
        }

        if (! $user->needsOnboarding()) {
            $user->nickname_changed_at = now();
        }

        $user->nickname = $nickname;
        if (empty($user->name)) {
            $user->name = $nickname;
        }
    }

    private function completeOnboarding(User $user): void
    {
        $user->onboarding_completed_at = $user->onboarding_completed_at ?? now();
    }

    private function emailTaken(string $email, User $user): bool
    {
        return User::query()->whereRaw('lower(email) = ?', [$email])->whereKeyNot($user->id)->exists();
    }

    /** Ties a confirmation link to the address it replaces, so older links stop working after any change. */
    private function emailFingerprint(User $user): string
    {
        return substr(hash_hmac('sha256', strtolower((string) $user->email), (string) config('app.key')), 0, 16);
    }
}
