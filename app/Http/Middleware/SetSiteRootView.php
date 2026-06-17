<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\ResponseFactory;

class SetSiteRootView
{
    public function handle(Request $request, Closure $next)
    {
        app(ResponseFactory::class)->setRootView('site');
        return $next($request);
    }
}
