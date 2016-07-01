<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;

class Permission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //dd($request->segments());
        return $next($request);
    }
}
