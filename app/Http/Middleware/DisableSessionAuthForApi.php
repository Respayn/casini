<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableSessionAuthForApi
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/*')) {
            config(['session.driver' => 'array']);
        }
        return $next($request);
    }
}
