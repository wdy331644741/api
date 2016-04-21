<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Models\Activity;
use App\Models\Rule;
use App\Models\Award;

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
            'start_at'=> 'required|date',
            'end_at' => 'required|date',
            'trigger_type'=>'required',
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
        $res = Activity::where('id',$request->id)->update(['enable'=>1,'publish_time'=>date('Y-m-d H:i:s')]);
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



    //---------------------------- 规则相关 ----------------------------------//



    public function postRuleAdd(Request $request,$model_name)
    {
        $type = $this->getStorageTypeByName($model_name);
        $func_name = 'rule_'.strtolower($model_name);
        $res = $this->$func_name($type,$request);
        if(!$res['error_code']){
            return $this->outputJson(0,array('insert_id'=>$res['insert_id']));
        }else{
            return $this->outputJson(10001,array('error_msg'=>$res['error_msg']));
        }
    }

    //删除
    public function postRuleDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'alpha_num|exists:rules,id',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rule_model = Rule::find($request->id);
        Rule::destroy($request->id);
        $type = $rule_model->rule_type;
        $res = $rule_model->getRuleByType($type)->delete();
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //获取rules
    public function getRuleConfig(){
        $rules = config('activity.rule_child');
        $new_rules = array();
        foreach($rules as $key=>$val){
            unset($val['model_path']);
            $new_rules[$key] = $val;
        }
        return $this->outputJson(0,$new_rules);
    }

    //获取活动规则
    public function getRuleList($activity_id){
        $rule_child = Rule::where('activity_id',$activity_id)->get();
        $rules = array();
        foreach($rule_child  as $val){
            $model_name = config('activity.rule_child.'.$val->rule_type.'.model_name');
            $func_name  = 'get'.$model_name.'Rule';
            /*if(function_exists($func_name)){
                return $this->outputJson(10000,array('error_msg'=>'Server Error'));
            }*/
            $child_rule = $this->$func_name($val->rule_id);
            $rules[] = array_merge($child_rule,array('rule_type'=>strtolower($model_name)));
        }
        return $this->outputJson(0,$rules);
    }

    //手动触发调用
    public function postReceive(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rules = $this->getRulelist($request->id);
        $rule_obj = json_decode($rules);
        if($rule_obj->error_code  == 0){
            $rule_list = $rule_obj->data;
            //1、获取用户信息
            //2、发奖励，存储参与记录

        }
    }
    //添加规则
    private function rule_register($type,$request){
        $validator = Validator::make($request->all(), [
            'min_time' => 'required',
            'max_time' => 'required',
            'activity_id'=>'alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        DB::beginTransaction();
        $obj =  new Rule\Register();
        $obj->min_time = $request->min_time;
        $obj->max_time = $request->max_time;
        $obj->save();
        if(!$obj->id){
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_id = $obj->id;
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    private function rule_channel($type,$request){
        $validator = Validator::make($request->all(), [
            'channels' => 'required',
            'activity_id'=>'alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        DB::beginTransaction();
        $obj = new Rule\Channel();
        $obj->channels = $request->channels;
        $obj->save();
        if(!$obj->id){
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_id = $obj->id;
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //获取规则
    private function getRegisterRule($rule_id){
        $register = new Rule\Register();
        $res = $register::where('id',$rule_id)->first()->toArray();
        return $res;
    }

    private function getChannelRule($rule_id){
        $register = new Rule\Channel();
        $res = $register::where('id',$rule_id)->first()->toArray();
        return $res;
    }

    private function getStorageTypeByName($type_name){
        $rules = config('activity.rule_child');
        foreach($rules as $key=>$val){
            if($val['model_name'] == ucfirst($type_name)){
                return $key;
            }
        }
    }
    private function getUserInfo(){

    }

    private function getUserCastInfo(){

    }
    /**
     * 添加奖品映射关系
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postAwardAdd(Request $request){
        //奖品类型
        $award_type = intval($request->award_type);
        if(empty($award_type)){
            return $this->outputJson(PARAMS_ERROR,array('award_type'=>'奖品类型id不能为空'));
        }
        //活动ID
        $activity_id = intval($request->activity_id);
        if(empty($activity_id)){
            return $this->outputJson(PARAMS_ERROR,array('activity_id'=>'活动id不能为空'));
        }
        //优惠券id
        $award_id = intval($request->award_id);
        if(empty($award_id)){
            return $this->outputJson(PARAMS_ERROR,array('award_id'=>'奖品id不能为空'));
        }
        $awardID = $this->_awardAdd($award_type,$award_id,$activity_id);
        if($awardID){
            return $this->outputJson(0,array('insert_id'=>$awardID));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'插入奖品关系表失败'));
        }
    }

    /**
     * 获取奖品映射关系列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postAwardList(Request $request){
        //奖品类型
        $where['award_type'] = intval($request->award_type);
        if(empty($where['award_type'])){
            return $this->outputJson(PARAMS_ERROR,array('award_type'=>'奖品类型id不能为空'));
        }
        //活动ID
        $where['activity_id'] = intval($request->activity_id);
        if(empty($where['activity_id'])){
            return $this->outputJson(PARAMS_ERROR,array('activity_id'=>'活动id不能为空'));
        }
        $list = Award::where($where)->get()->toArray();
        return $this->outputJson(0,$list);
    }
    /**
     * 删除奖品映射关系
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postAwardDelete(Request $request){
        //奖品类型
        $where['award_type'] = intval($request->award_type);
        if(empty($where['award_type'])){
            return $this->outputJson(PARAMS_ERROR,array('award_type'=>'奖品类型id不能为空'));
        }
        //活动ID
        $where['activity_id'] = intval($request->activity_id);
        if(empty($where['activity_id'])){
            return $this->outputJson(PARAMS_ERROR,array('activity_id'=>'活动id不能为空'));
        }
        //优惠券id
        $where['award_id'] = intval($request->award_id);
        if(empty($where['award_id'])){
            return $this->outputJson(PARAMS_ERROR,array('award_id'=>'奖品id不能为空'));
        }
        $status = Award::where($where)->delete();
        if($status){
            return $this->outputJson(0,array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }
    /**
     * 添加到awards
     * @param $award_type
     * @param $award_id
     * @param $activityID
     * @return mixed
     */
    function _awardAdd($award_type,$award_id,$activityID){
        $data['activity_id'] = $activityID;
        $data['award_type'] = $award_type;
        $data['award_id'] = $award_id;
        //查看是否重复
        $count = Award::where($data)->count();
        if($count > 0){
            return false;
        }
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $id = Award::insertGetId($data);
        return $id;
    }
    /*case 2:
            $validator = Validator::make($request->all(), [
                'is_invite' => 'required',
            ]);
            if($validator->fails()){
                return $this->outputJson(10000,$validator->errors());
            }
            $obj = new $model_name;
            $obj->is_invite = $request->is_invite;
            break;
        case 3:
            $validator = Validator::make($request->all(), [
                'invitenum' => 'required|alpha_num',
            ]);
            if($validator->fails()){
                return $this->outputJson(10000,$validator->errors());
            }
            $obj = new $model_name;
            $obj->invites = $request->invites;
            break;
        case 4:
            $validator = Validator::make($request->all(), [
                'min_level' => 'required|alpha_num',
                'max_level' => 'required|alpha_num',
            ]);
            if($validator->fails()){
                return $this->outputJson(10000,$validator->errors());
            }
            $obj = new $model_name;
            $obj->min_level = $request->min_level;
            $obj->max_level = $request->max_level;
            break;
        case 5:
            $validator = Validator::make($request->all(), [
                'min_credit' => 'required|alpha_num',
                'max_credit' => 'required|alpha_num',
            ]);
            if($validator->fails()){
                return $this->outputJson(10000,$validator->errors());
            }
            $obj = new $model_name;
            $obj->min_credit = $request->min_credit;
            $obj->max_credit = $request->max_credit;
            break;
        case 6:
            $validator = Validator::make($request->all(), [
                'min_balance' => 'required|alpha_num',
                'max_balance' => 'required|alpha_num',
            ]);
            if($validator->fails()){
                return $this->outputJson(10000,$validator->errors());
            }
            $obj = new $model_name;
            $obj->min_balance = $request->min_balance;
            $obj->max_balance = $request->max_balance;
            break;
        case 7:
            $validator = Validator::make($request->all(), [
                'min_firstcast' => 'required|alpha_num',
                'max_firstcast' => 'required|alpha_num',
            ]);
            if($validator->fails()){
                return $this->outputJson(10000,$validator->errors());
            }
            $obj = new $model_name;
            $obj->min_firstcast = $request->min_firstcast;
            $obj->max_firstcast = $request->max_firstcast;
            break;
        case 8:
            $validator = Validator::make($request->all(), [
                'min_cast' => 'required|alpha_num',
                'max_cast' => 'required|alpha_num',
            ]);
            if($validator->fails()){
                return $this->outputJson(10000,$validator->errors());
            }
            $obj = new $model_name;
            $obj->min_cast = $request->min_cast;
            $obj->min_cast = $request->min_cast;
            break;*/
}
