<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            in_array($request->method(), ['GET', 'HEAD']) &&
            $request->is('api/*') &&
            !$request->header('Authorization')
        ) {
            $response->headers->set('Cache-Control', 'public, max-age=30');
        }

        return $response;
    }
}
