<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use App\Models\Record;
use Config;

class OpRecord
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
        global $userId;
        Record::create([
            'user_id' => $userId || 0,
            'method' => $request->method(),
            'host' => $request->getHttpHost(),
            'path' => $request->path(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->header('User-Agent'),
            'query' => $request->getQueryString(),
            'post' => json_encode($_POST, JSON_UNESCAPED_UNICODE),
        ]);
        return $next($request);
    }
}
