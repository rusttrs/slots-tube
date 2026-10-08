<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show()
    {
        return view('pages.profile', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'nickname' => ['required', 'string', 'min:2', 'max:40'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->nickname = $data['nickname'];
        if (empty($user->name)) {
            $user->name = $data['nickname'];
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'r2');
            if (! is_string($path)) {
                throw ValidationException::withMessages(['avatar' => 'Could not upload the image. Please try again.']);
            }
            $user->avatar_path = $path;
        } elseif ($request->boolean('random_avatar')) {
            $user->avatar_path = null;
        }

        $user->onboarding_completed_at = $user->onboarding_completed_at ?? now();
        $user->save();

        return redirect()->to(localized_url(null, 'profile'))
            ->with('profile_saved', true);
    }
}
