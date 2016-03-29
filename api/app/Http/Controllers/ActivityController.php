<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Activity;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Validator;

class ActivityController extends Controller
{
    //

    public function postAdd(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'start_at'=> 'required|date',
            'end_at' => 'required|date',
            'trigger_id'=>'required|exists:triggers.id',
            'des'=>'required',
            'enable'=> 'required|in:0,1'
        ]);
        if($validator->fails()){
            return $this->outputJson(10000,$validator->errors());
        }
        $activity = new Activity;
        $activity->name = $request->name;
        $activity->start_at = $request->start_at;
        $activity->end_at = $request->end_at;
        $activity->name = $request->name;
        $activity->trigger_id = $request->trigger_id;
        $activity->des = $request->des;
        $activity->enable = $request->enable;
        $res = $activity->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$activity->id));
        }else{
            return $this->outputJson(10000,array('error_msg'=>'Insert Failed!'));
        }
    }

    public function getIndex() {
       $data = Activity::all();
       return $this->outputJson(0,$data);
    }

    public function getDel(Request $request){
        if(isset($request->one)){
            DB::transaction(function($request){
                Activity::destroy($request->one);

                //删除子规则
            });

                return $this->outputJson(0,array('error_msg'=>'Success!'));

                return $this->outputJson(10001,array('error_msg'=>'Delete Failed!'));

        }
    }

    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|alpha_num',
            'name' => 'required|min:2|max:255',
            'start_at'=> 'required|date',
            'end_at' => 'required|date',
            'trigger_id'=>'required|exists:triggers.id',
            'des'=>'required',
            'enable'=> 'required|in:0,1'
        ]);
        if($validator->fails()){
            return $this->outputJson(10000,$validator->errors());
        }

        $res = Activity::where('id',1)->update([
            'name'=>$request->name,
            'start_at'=>$request->start_at,
            'end_at'=>$request->end_at,
            'trigger_id'=>$request->trigger_id,
            'des'=>$request->des,
            'enable'=>$request->enable,
        ]);
        if($res){
            return $this->outputJson(0,array('error_msg'=>'Success!'));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Update Failed!'));
        }
    }
}
