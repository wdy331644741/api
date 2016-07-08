<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function outputJson($errorCode, $data = null){
        return response()->json(array('error_code'=> $errorCode, 'data'=>$data));
    }
    
    protected function outputRpc($res) {
        if(isset($res['error'])){
            $response['error_code']  = $res['error']['code'];      
            $response['data'] = array( 'error_msg' => $res['error']['message']);
        }else{
            $response['error_code']  = $res['result']['code'];
            $response['data'] = isset($res['result']['data']) ? $res['result']['data'] : [];
        }
        return response()->json($response);
    }
}
