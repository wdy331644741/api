<?php

namespace App\Http\Middleware;

use Closure;
use Lib\Session as SessionHandler; 

class Session
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
        ini_set("session.save_handler", "redis");
        ini_set("session.save_path", "tcp://192.168.10.36:6379");
        ini_set("session.name", "WANGLIBAO_TOKEN");
        ini_set("session.cookie_domain", "wanglibao.com");
        $sessionHandler = new SessionHandler();
        $sessionHandler->selectSessionGroup('account.dev.wanglibao.com');
        global $userId;
        $userId = $sessionHandler->get('userData.user_id');
        
        return $next($request);
    }
}
