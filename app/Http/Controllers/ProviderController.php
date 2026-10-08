<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\Slot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        if ($request->route('locale') && $request->route('slug')) {
            $slug = (string) $request->route('slug');
        } elseif ($request->route('slug')) {
            $slug = (string) $request->route('slug');
        }

        $provider = Provider::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $slots = Slot::query()
            ->published()
            ->where('provider_id', $provider->id)
            ->latest('published_at')
            ->latest('id')
            ->get();

        return view('providers.show', [
            'provider' => $provider,
            'slots' => $slots,
        ]);
    }
}
