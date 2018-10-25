<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Config,DB;

class HockeyController extends Controller
{
    //集卡活动添加&修改
    public function postCardOperation(){

    }
    //竞猜活动添加
    public function postGuessAdd(){

    }
    //竞猜活动修改比分&开奖
    public function postGuessOperation(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:0|max:1',
            'first_master_score' => 'required|min:1|max:32',
            'first_visiting_score' => 'required|min:1|max:64',
            'second_master_score' => 'required|integer|min:0|max:1',
            'second_visiting_score' => 'required|integer|min:0|max:1',
            'third_master_score' => 'required|integer|min:0|max:1',
            'third_visiting_score' => 'required|integer|min:0|max:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //修改
    }

}
