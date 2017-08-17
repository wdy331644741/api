<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use App\Models\Admin;
use Config;

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
        global $phone;
        if(!env('ADMIN_AUTH', true)){
            return $next($request);
        }
        $admin = Admin::where('mobile', $phone)->with('privilege')->first();
        if(!$admin || !$admin['privilege']) {
            $privilege = false;
        }else{
            $privilege = $admin['privilege'] ? json_decode($admin['privilege']['privilege'], true) : false;
        }
        $res = $this->checkPermission($request->segments(), $privilege);
        $isAdministrator = Config("administrator.{$phone}");
//        if(!$res && !$isAdministrator) {
//            if(!$phone) {
//                return response()->json(array('error_code'=> '11002', 'data'=>array('error_msg' => '账号未登录')));
//            }
//            return response()->json(array('error_code'=> '11001', 'data'=>array('error_msg' => '账号权限不足, 请联系管理员')));
//        }
        return $next($request);
    }



    private function checkPermission($segments, $privilege) {
        $segment = $segments[0];
        if($segment === 'account') {
            return true;
        }
        if(empty($privilege)) {
            return false;
        }
        if(isset($privilege['default']) && $privilege['default']) {
            if(!isset($privilege['deny'])) {
                return true;
            }
            if(is_array($privilege['deny']) && in_array($segment, $privilege['deny'])) {
                return false;
            }
            return true;
        }
        if(isset($privilege['default']) && !$privilege['default']) {
            if(!isset($privilege['allow'])) {
                return false;
            }
            if(is_array($privilege['allow']) && in_array($segment, $privilege['allow'])) {
                return true;
            }
            return false;
        }
        return false;
    }

    /*
    private function handleRequest() {
        $action = Route::currentRouteAction();
        $actionArr = explode('@', $action);
        dd($actionArr);
    }
    */
}
