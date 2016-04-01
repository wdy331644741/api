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
            'alias_name'=>'required|alpha_dash|unique',
            'start_at'=> 'date',
            'end_at' => 'date',
            'trigger_type'=>'required',
            'des'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10000,$validator->errors());
        }
        $activity = new Activity;
        $activity->name = $request->name;
        $activity->start_at = $request->start_at;
        $activity->end_at = $request->end_at;
        $activity->alias_name = $request->alias_name;
        $activity->trigger_type = $request->trigger_type;
        $activity->des = $request->des;
        $activity->enable = 0;
        $res = $activity->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$activity->id));
        }else{
            return $this->outputJson(10004,array('error_msg'=>'Insert Failed!'));
        }
    }
    //排序，分页
    public function getIndex() {
        $data = Activity::where('enable',1)->orderBy('id','desc')->paginate(20);
        return $this->outputJson(0,$data);
    }

    public function getDel(Request $request){
        if(isset($request->one)){
            $activity = Activity::find($request->one);
            $res = $activity->delete();
            if($res){
                return $this->outputJson(0,array('error_msg'=>'ok'));
            }else{
                return $this->outputJson(10001,array('error_msg'=>'Delete Failed!'));
            }

        }
    }

    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|alpha_num',
            'name' => 'required|min:2|max:255',
            'alias_name'=>'required|alpha_dash|unique',
            'start_at'=> 'required|date',
            'end_at' => 'required|date',
            'trigger_type'=>'required',
            'des'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10000,$validator->errors());
        }

        $res = Activity::where('id',$request->id)->update([
            'name'=>$request->name,
            'alias_name'=>$request->alias_name,
            'start_at'=>$request->start_at,
            'end_at'=>$request->end_at,
            'trigger_type'=>$request->trigger_type,
            'des'=>$request->des,
        ]);
        if($res){
            return $this->outputJson(0,array('error_msg'=>'ok'));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Update Failed!'));
        }
    }

    //发布活动
    public function getRelease($activity_id){
        if(!$activity_id){
            return $this->outputJson(10003,array('error_msg'=>"Params Error!"));
        }
        $res = Activity::where('id',$activity_id)->where('enable',0)->update(['enable'=>1]);
        if($res){
            return $this->outputJson(0,array('error_msg'=>"ok"));
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Update Failed!"));
        }
    }

    //活动详情
    public function getInfo($activity_id){
        if(!$activity_id){
            return $this->outputJson(10003,array('error_msg'=>"Params Error!"));
        }
        $res = Activity::where('id',$activity_id)->where('enable',1)->findOrFail($activity_id);
        return $this->outPutJson(0,$res);
    }
}
