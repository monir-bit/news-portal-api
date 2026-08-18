<?php

namespace App\Services;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class SlowQueryLoggerService
{
    public static function register(): void
    {
        if (! config('app.slow_query_log')) {
            return;
        }
        DB::listen(function (QueryExecuted $query) {
            if ($query->time > 100) {
                Log::channel('query')->warning('Slow SQL', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,

                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'route' => optional(request()->route())->getName(),
                    'action' => optional(request()->route())->getActionName(),
                ]);
            }
        });
    }
}
