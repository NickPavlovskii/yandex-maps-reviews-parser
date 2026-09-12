<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParserSyncIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('parser.sync_enabled'), 404);

        return $next($request);
    }
}
