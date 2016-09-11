<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use App\Models\Admin;
use Config;

class CheckInternal
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
        $ip = $request->getClientIp();
        $ipString = env('INTERNAL_IPS', '');
        $ipList = explode(',', $ipString);
        $res = array_search($ip, $ipList);
        if($res === false) {
            return response()->json(array('error_code'=> '11002', 'data'=>array('error_msg' => 'IP 不合法')));
        }
        return $next($request);
    }
}
