<?php

namespace App\Http\Controllers;

use App\Services\GoogleTranslate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranslateController extends Controller
{
    public function __invoke(Request $request, GoogleTranslate $translator): JsonResponse
    {
        $data = $request->validate([
            'target' => ['required', 'string', 'max:8'],
            'texts' => ['required', 'array', 'min:1', 'max:20'],
            'texts.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $target = strtolower(substr($data['target'], 0, 2));
        $texts = array_map(static fn ($t) => (string) ($t ?? ''), $data['texts']);

        return response()->json([
            'target' => $target,
            'translations' => $translator->translateMany($texts, $target),
        ]);
    }
}
