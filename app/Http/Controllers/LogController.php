<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\JsonRpc;
use App\Models\Log;
use Validator;


class LogController extends Controller
{
    public function __construct() {
        $this->jsonRpc = new JsonRpc();
    }
    public function postIndex(Request $request){
        $params = $request->all();
        $validator = Validator::make($params, [
            'type'  => 'required',
            'data' => 'required',
        ]);
        $res = $this->jsonRpc->account()->profile();

        if(isset($res['error'])){
            //未登陆用户
            return $this->outputJson(0);
        }

        $logData = new Log();
        $logData->user_id = $res['result']['data']['id'];
        $logData->type = $request->type;
        $logData->data = $request->data;
        $logData->save();


        return $this->outputJson(200,'success');
    }
}
