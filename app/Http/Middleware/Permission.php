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
        $admin = Admin::where('mobile', $phone)->first();
        if(!$admin || !$admin['level']) {
            $level = 0;
        }else{
            $level = $admin['level'] ? $admin['level'] : 0;
        }
        $res = $this->checkPermission($request->segments(), $level);
        $isAdministrator = Config("administrator.{$phone}");
        if(!$res && !$isAdministrator) {
            return response()->json(array('error_code'=> '11001', 'data'=>array('error_msg' => '账号权限不足, 请联系管理员')));
        }
        return $next($request);
    }
    

    
    private function checkPermission($segments, $level) {
        $permission = Config::get("permission.{$level}");
        foreach($segments as $value) {
            if(isset($permission['items']) && isset($permission['items'][$value])) {
                $permission = $permission['items'][$value];
                continue;
            }
        }
        return($permission['default']);
    }
    
    /* 
    private function handleRequest() {
        $action = Route::currentRouteAction();
        $actionArr = explode('@', $action);
        dd($actionArr);
    }
    */
}
