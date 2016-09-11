<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\JsonRpc;
use App\Models\Admin;
use Validator, Config;

class AccountController extends Controller
{
    public function __construct() {
        $this->jsonRpc = new JsonRpc();
    }
    
    public function postLogin(Request $request) {
        $params = $request->all();
        $validator = Validator::make($params, [
            'username'  => 'required', 
            'password' => 'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001, array('error_msg'=>$validator->errors()->first()));
        }

        $result = $this->jsonRpc->account()->signin($params);
        return $this->outputRpc($result);
    }
    
    public function getCaptcha() {
        $result = $this->jsonRpc->account()->captcha();
        return $this->outputRpc($result);                               
    }
    
    public function getProfile() {
        $res = $this->jsonRpc->account()->profile();

        if(isset($res['error'])){
            $response['error_code']  = $res['error']['code'];      
            $response['data'] = array( 'error_msg' => $res['error']['message']);
            return response()->json($response);
        }
    
        $response['error_code']  = $res['result']['code'];
        $data = isset($res['result']['data']) ? $res['result']['data'] : [];

        $mobile = $data['phone'];
        $admin = Admin::where('mobile', $mobile)->first();
        if(!$admin || !$admin['level']) {
            $level = 0;
        }else{
            $admin->last_login = date('Y-m-d H:i:s');
            $admin->update();
            $level = $admin['level'] ? $admin['level'] : 0;
        }
        $permission = Config::get("permission.{$level}");
        $data['permission'] = $permission;
        $response['data'] = $data;
        return response()->json($response);
    }
    public function getLogout() {
        $result = $this->jsonRpc->account()->signout();
        return $this->outputRpc($result);       
    }
}
