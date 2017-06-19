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

        // 过滤账号信息信息
        $post = $_POST;
        if(isset($post['password'])) {
            $post['password']  = '******';
        }
        Record::create([
            'user_id' => $userId || 0,
            'method' => $request->method() || '',
            'host' => $request->getHttpHost() || '',
            'path' => $request->path() || '',
            'ip' => $request->getClientIp() || '',
            'user_agent' => $request->header('User-Agent'),
            'query' => $request->getQueryString() || '',
            'post' => json_encode($post, JSON_UNESCAPED_UNICODE),
        ]);
        return $next($request);
    }
}
