<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'age' => ['accepted'],
            'newsletter' => ['accepted'],
        ], [
            'age.accepted' => 'Please confirm that you are over 18.',
            'newsletter.accepted' => 'Please opt in to the newsletter.',
        ]);

        $subscriber = NewsletterSubscriber::subscribe($data['email'], app()->getLocale());

        Mail::to($subscriber->email)->send(new NewsletterWelcomeMail($subscriber));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Thanks! Please check your inbox.',
            ]);
        }

        return back()->with('newsletter_success', 'Thanks! Please check your inbox.');
    }

    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->firstOrFail();
        $subscriber->forceFill(['unsubscribed_at' => now()])->save();

        return response()->view('pages.stub', [
            'title' => 'Unsubscribed',
            'heading' => 'You have been unsubscribed',
            'text' => 'You will no longer receive newsletter emails from slots.tube.',
        ]);
    }
}
