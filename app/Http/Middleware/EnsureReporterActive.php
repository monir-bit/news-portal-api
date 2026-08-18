<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReporterActive
{
    /**
     * If reporter is inactive: revoke token, return 403 with message.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $reporter = auth('sanctum')->user();

        if (!$reporter) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!$reporter->is_active) {
            $reporter->currentAccessToken()?->delete();

            return response()->json([
                'success' => false,
                'message' => 'আপনার অ্যাকাউন্টটি ইনঅ্যাক্টিভ আছে, যোগাযোগ করুন',
                'code' => 'reporter_inactive',
            ], 403);
        }

        return $next($request);
    }
}
