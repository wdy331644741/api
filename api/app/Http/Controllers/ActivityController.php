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
            'alias_name'=>'required|alpha_dash|unique:activities,alias_name',
            'start_at'=> 'date',
            'end_at' => 'date',
            'trigger_type'=>'required',
            'des'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
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
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
    //排序，分页
    public function getIndex() {
        $data = Activity::orderBy('id','desc')->paginate(20);
        return $this->outputJson(0,$data);
    }

    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $activity = Activity::find($request->id);
        $res = $activity->delete();
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|alpha_num',
            'name' => 'required|min:2|max:255',
            'alias_name'=>'required|alpha_dash|unique:activities,alias_name',
            'start_at'=> 'required|date',
            'end_at' => 'required|date',
            'trigger_type'=>'required',
            'des'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
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
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //发布活动
    public function postRelease(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Activity::where('id',$request->id)->update(['enable'=>1]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //活动详情
    public function getInfo($activity_id){
        if(!$activity_id){
            return $this->outputJson(10001,array('error_msg'=>"Parames Error"));
        }
        $res = Activity::where('id',$activity_id)->where('enable',1)->findOrFail($activity_id);
        if(!$res){
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
        return $this->outPutJson(0,$res);
    }
}
