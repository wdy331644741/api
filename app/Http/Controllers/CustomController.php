<?php

namespace App\Http\Controllers;

use App\Models\HdCustom;
use App\Models\HdCustomAward;
use Illuminate\Http\Request;

use App\Service\Attributes;
use Excel;
use Validator;
use Response;
use Config;

class CustomController extends Controller
{
    //添加活动
    public function postAdd(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:255',
            'start_at'=> 'date',
            'end_at' => 'date',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $activity = new HdCustom();
        $activity->name = $request->name;
        if($request->start_at){
            $activity->start_at = $request->start_at;
        }
        if($request->end_at){
            $activity->end_at  = $request->end_at;
        }
        $activity->status = 0;
        $res = $activity->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$activity->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
    //排序，分页
    public function getIndex() {
        $data = HdCustom::with('customAwards')->orderBy('id','desc')->paginate(20);
        return $this->outputJson(0,$data);
    }

    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|alpha_num|exists:hd_custom_award,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $activity = HdCustomAward::find($request->id);
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
//            'name' => 'required|min:1|max:255',
            'start_at'=> 'date',
            'end_at' => 'date',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = HdCustom::where('id',$request->id)->update([
//            'name'=>$request->name,
            'start_at'=>$request->start_at ? $request->start_at : NUll,
            'end_at'=>$request->end_at ? $request->end_at : NUll,
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

        $status = HdCustom::where('status', 1)->first();
        if ($status) {
            return $this->outputJson(10001,array('error_msg'=>'只能上线一个'));
        }
        $res = HdCustom::where('id',$request->id)->update(['status'=>1]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //下线活动
    public function postOffline(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = HdCustom::where('id',$request->id)->update(['status'=>0]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //添加奖品
    public function postAwardAdd(Request $request){
        $investment_time = implode(',',array_keys(Config::get('custom.investment_time')));
        $validator = Validator::make($request->all(), [
            'effective_time_day' => 'required|integer',
            'min'=>'required|integer',
            'max'=>'required|integer',
            'type'=>'required',
            'investment_time'=>'required|in:'. $investment_time,
            'award_money'=>'required|numeric',
            'custom_id'=>'required|numeric',
        ], [
            'effective_time_day.required' => '福利券有效期不能为空',
            'min.required' => '出借金额不能为空',
            'max.required' => '出借金额不能为空',
            'award_money.required' => '福利券额度不能为空',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $group = new HdCustomAward();
        $group->effective_time_day = $request->effective_time_day;
        $group->min = $request->min;
        $group->max = $request->max;
        $group->type = $request->type;
        $group->investment_time = $request->investment_time;
        $group->award_money = $request->award_money;
        $group->custom_id = $request->custom_id;
        if ($group->type == 2) {
            $group->name = ($group->award_moey * 100) . '%';
        } else if ($group->type == 1) {
            $group->name = $group->award_money . '元';
        }
        $res = $group->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$group->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //获取红包类型
    public function getTypeList(){
        $data = config('custom.type');
        return $this->outputJson(0,$data);
    }

    //获取出借期限类型
    public function getInvestmentTypes(){
        $data = config('custom.investment_time');
        return $this->outputJson(0,$data);
    }
    //修改奖品
    public function postAwardPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'effective_time_day' => 'required|integer',
            'min'=>'required|integer',
            'max'=>'required|integer',
            'type'=>'required',
            'investment_time'=>'required',
            'award_money'=>'required|numeric',
        ], [
            'effective_time_day.required' => '福利券有效期不能为空',
            'min.required' => '出借金额不能为空',
            'max.required' => '出借金额不能为空',
            'award_money.required' => '福利券额度不能为空',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $updata = [
            'effective_time_day' => $request->effective_time_day,
            'min' => $request->min,
            'max' => $request->max,
            'type' => $request->type,
            'investment_time' => $request->investment_time,
            'award_money' => $request->award_money,
        ];
        if ($request->type == 2) {
            $updata['name'] = $request->award_money . '%';
            $updata['award_money'] = $request->award_money / 100;
        } else if ($request->type == 1) {
            $updata['name'] = $request->award_money . '元';
        }

        $res = HdCustomAward::where('id',$request->id)->update($updata);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }
}
