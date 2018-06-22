<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\JsonRpc;
use App\Models\Log;
use Validator;

class LogController extends Controller
{
    protected $fileds = ['id', 'user_id', 'type', 'data', 'updated_at'];
    public function __construct() {
        $this->jsonRpc = new JsonRpc();
        $this->model = new Log();
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

    public function getList (Request $request) {
        $params = [];
        $data = Log::where($params)->OrderBy('id','desc')->paginate(20);
        if(isset($request->start_time) && isset($request->end_time) && !empty($request->start_time) && !empty($request->end_time)){
            $data = Log::where($params)
                ->where('updated_at','>=',$request->start_time)
                ->where('updated_at','<=',$request->end_time)
                ->OrderBy('id','desc')->paginate(20);
        } else if(!empty($request->start_time)){
            $data = Log::where($params)->where('updated_at','>=',$request->start_time)->OrderBy('id','desc')->paginate(20);
        } else if(!empty($request->end_time)){
            $data = Log::where($params)->where('updated_at','<=',$request->end_time)->OrderBy('id','desc')->paginate(20);
        }
        return $this->outputJson(0,$data);
    }
}
