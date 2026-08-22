<?php

/*
|--------------------------------------------------------------------------
| API v2 routes
|--------------------------------------------------------------------------
|
| Loaded under the "api" middleware group with prefix /api/v2 (see
| bootstrap/app.php). Runs alongside the existing /api routes untouched —
| nothing here is wired into the live frontend yet.
|
*/

require __DIR__.'/api_v2/news.php';
require __DIR__.'/api_v2/reporter.php';
require __DIR__.'/api_v2/epaper.php';
require __DIR__.'/api_v2/worldcup.php';
require __DIR__.'/api_v2/engagement.php';
require __DIR__.'/api_v2/election.php';
require __DIR__.'/api_v2/misc.php';
