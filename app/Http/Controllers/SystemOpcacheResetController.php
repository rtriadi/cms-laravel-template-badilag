<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemOpcacheResetController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $nonce = $request->query('nonce');
        if (! is_string($nonce) || $nonce === '' || Cache::pull('cms_opcache_nonce:'.$nonce) !== true) {
            abort(403);
        }

        clearstatcache(true);

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        return response()->json(['ok' => true], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
