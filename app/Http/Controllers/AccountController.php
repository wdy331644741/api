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
        $privilege = ['default' => false, 'allow' => []];
        $isAdmin = false;

        $res = $this->jsonRpc->account()->profile();

        if(isset($res['error'])){
            $response['error_code']  = $res['error']['code'];
            $response['data'] = array( 'error_msg' => $res['error']['message']);
            return response()->json($response);
        }

        $response['error_code']  = $res['result']['code'];
        $data = isset($res['result']['data']) ? $res['result']['data'] : [];

        $mobile = $data['phone'];
        $admin = Admin::where('mobile', $mobile)->with('privilege')->first();
        if($admin) {
            $isAdmin = true;
            $admin->last_login = date('Y-m-d H:i:s');
            $admin->update();
            if(isset($admin['privilege'])
                && isset($admin['privilege']['privilege'])
                && !empty($admin['privilege']['privilege'])
            ) {
                $jsonRes = json_decode($admin['privilege']['privilege'],true);
                if(!empty($jsonRes)) {
                    $privilege = $jsonRes;
                }
            }
        }
        $data['privilege'] = $privilege;
        $data['is_admin'] = $isAdmin;
        $response['data'] = $data;
        return response()->json($response);
    }
    public function getLogout() {
        $result = $this->jsonRpc->account()->signout();
        return $this->outputRpc($result);
    }
}
