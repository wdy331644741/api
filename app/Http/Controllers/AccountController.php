<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\JsonRpc;
use Validator;

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
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        
        $result = $this->jsonRpc->account()->signin($params);
        return $this->outputRpc($result);
    }
    
    public function getTest() {
        global $userId;
    }
}
