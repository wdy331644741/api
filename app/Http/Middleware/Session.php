<?php

namespace App\Http\Middleware;

use Closure;
use Lib\Session as SessionHandler;
use Config;

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
//        ini_set("session.save_handler", Config::get('rpcsession.handler'));
//        ini_set("session.save_path", Config::get('rpcsession.path'));
//        ini_set("session.name", Config::get('rpcsession.name'));
//        ini_set("session.cookie_domain", Config::get('rpcsession.domain'));

        $sessionHandler = new SessionHandler();
        $sessionHandler->setGroup(env('ACCOUNT_BASE_HOST'));
        global $userId,$phone,$requestIP;
        $userId = $sessionHandler->get('userData.user_id');
        $phone = $sessionHandler->get('userData.user_name');
        $requestIP = $request->getClientIp();
        return $next($request);
    }
}
