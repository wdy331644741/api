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
        return response()->json($this->rpc2json($res));
    }

    protected function outputNotCodeJson($arr=array()){
        return response()->json($arr);
    }
    
    protected function rpc2json($res) {
        if(isset($res['error'])){
            $response['error_code']  = $res['error']['code'];      
            $response['data'] = array( 'error_msg' => $res['error']['message']);
        }elseif(isset($res['result'])){
            foreach ($res['result'] as $key => $value) {
                if($key === 'code'){
                    $response['error_code']  = $res['result']['code'];
                    continue;
                } 
                $response[$key] = $res['result'][$key];
            }
            $response['error_code']  = $res['result']['code'];
        }
        return $response;
    }
}
