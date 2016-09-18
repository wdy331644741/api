<?php

namespace App\Http\Controllers;

use App\Models\ActivityJoin;
use App\Models\AwardBatch;
use App\Models\SendRewardLog;
use App\Service\Func;
use Illuminate\Http\Request;

use App\Models\Activity;
use App\Models\Rule;
use App\Models\ActivityGroup;
use App\Models\Award;
use App\Models\AwardInvite;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Award6;
use App\Models\Coupon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\NullHandlerTest;
use Validator;

class ActivityController extends Controller
{
    //添加子活动
    public function postAdd(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'alias_name' =>'unique:activities,alias_name',
            'group_id'=>'required|exists:activity_groups,id',
            'frequency'=>'required',
            'start_at'=> 'date',
            'award_rule'=>'required|integer',
            'end_at' => 'date',
            'trigger_index'=>'required|integer',
            'trigger_type'=>'required',
            'join_max'=>'required|integer'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $activity = new Activity;
        $activity->name = $request->name;
        $activity->frequency = $request->frequency;
        $activity->alias_name = $request->alias_name;
        $activity->award_rule = $request->award_rule;
        if($request->start_at){
            $activity->start_at = $request->start_at;
        }
        if($request->end_at){
            $activity->end_at  = $request->end_at;
        }
        $activity->group_id = $request->group_id;
        $activity->trigger_index = $request->trigger_index;
        $activity->trigger_type = $request->trigger_type;
        $activity->join_max = $request->join_max;
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
            'alias_name' => 'unique:activities,alias_name,'.$request->id,
            'group_id'=>'required|exists:activity_groups,id',
            'frequency'=>'required',
            'award_rule'=>'required|integer',
            'start_at'=> 'date',
            'end_at' => 'date',
            'trigger_index'=>'required|integer',
            'trigger_type'=>'required',
            'join_max'=>'required|integer'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Activity::where('id',$request->id)->update([
            'name'=>$request->name,
            'alias_name'=>$request->alias_name,
            'group_id'=>$request->group_id,
            'frequency'=>$request->frequency,
            'start_at'=>$request->start_at ? $request->start_at : NUll,
            'award_rule'=>$request->award_rule,
            'end_at'=>$request->end_at ? $request->end_at : NUll,
            'trigger_index'=>$request->trigger_index,
            'trigger_type'=>$request->trigger_type,
            'join_max' => $request->join_max,
            'des'=>$request->des ? $request->des : NULL,
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

    //下线活动
    public function postOffline(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Activity::where('id',$request->id)->update(['enable'=>0]);
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
        $res = Activity::where('id',$activity_id)->findOrFail($activity_id);
        if(!$res){
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
        return $this->outPutJson(0,$res);
    }

    //添加主活动
    public function postGroupAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'string|required',
            'type_id'=>'required|integer',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $group = new ActivityGroup();
        $group->name = $request->name;
        $group->type_id = $request->type_id;
        $group->des = $request->des ? $request->des : NULL;
        $res = $group->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$group->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //获取活动类型
    public function getTypeList(){
        $data = config('activity.activity_type');
        return $this->outputJson(0,$data);
    }

    //修改主活动
    public function postGroupPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:activity_groups,id',
            'name' => 'required|string',
            'type_id'=>'required|integer',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $updata = [
            'name' => $request->name,
            'type_id' => $request->type_id,
        ];
        if($request->des){
            $updata['des'] = $request->des;
        }

        $res = ActivityGroup::where('id',$request->id)->update($updata);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //组列表
    public function getGroupList(Request $request){
        $data = Func::GroupSearch($request,new ActivityGroup);
        return $this->outputJson(0,$data);
    }

    //删除主活动
    public function postGroupDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:activity_groups,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }

        $actNum = Activity::where(['group_id'=>$request->id,'enable'=>1])->count();
        if($actNum){
            return $this->outputJson(10006,array('error_msg'=>'Related Activity Is Running'));
        }
        $res = ActivityGroup::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //查询单个组活动
    public function getGroupInfo($group_id){
        $get['group_id'] = $group_id;
        $validator = Validator::make($get, [
            'group_id' => 'exists:activity_groups,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $data = ActivityGroup::with('activities')->find($group_id);
        return $this->outputJson(0,$data);
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
        $res = Rule::destroy($request->id);
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
            $val->rule_info = json_decode($val->rule_info);
            $val->rule_type = strtolower($model_name);
            $rules[] = $val;
        }
        return $this->outputJson(0,$rules);
    }

    //注册时间
    private function rule_register($type,$request){
        $validator = Validator::make($request->all(), [
            'min_time' => 'date',
            'max_time' => 'date|after:min_time',
            'activity_id'=>'required|alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $request->min_time = $request->min_time ? $request->min_time : NULL;
        $request->min_time = $request->max_time ? $request->max_time : NULL;

        DB::beginTransaction();
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('min_time','max_time'));
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //用户渠道白名单
    private function rule_channel($type,$request){
        $validator = Validator::make($request->all(), [
            'channels' => 'required',
            'activity_id'=>'required|alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        DB::beginTransaction();
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('channels'));
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //用户渠道黑名单
    private function rule_channelblist($type,$request){
        $validator = Validator::make($request->all(), [
            'channels' => 'required',
            'activity_id'=>'required|alpha_num|exists:activities,id',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        DB::beginTransaction();
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('channels'));
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //充值金额
    private function rule_recharge($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'isfirst' => 'required|in:0,2',
            'min_recharge'=>'required|numeric',
            'max_recharge'=>'required|numeric|min:'.$request->min_recharge,
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        DB::beginTransaction();
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('isfirst','min_recharge','max_recharge'));
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //充值总金额
    private function rule_rechargeall($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'start_time'=>'required|date',
            'end_time'=>'required|date',
            'min_recharge_all'=>'required|numeric',
            'max_recharge_all'=>'required|numeric|min:'.$request->min_recharge_all,
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        DB::beginTransaction();
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('start_time','end_time','min_recharge_all','max_recharge_all'));
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //投资金额
    private function rule_cast($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'isfirst' => 'required|in:0,2',
            'min_cast'=>'required|numeric',
            'max_cast'=>'required|numeric|min:'.$request->min_cast,
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        DB::beginTransaction();
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('isfirst','min_cast','max_cast'));
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //投资总金额
    private function rule_castall($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'start_time'=>'required|date',
            'end_time'=>'required|date',
            'min_cast_all'=>'required|numeric',
            'max_cast_all'=>'required|numeric|min:'.$request->min_cast_all,
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        DB::beginTransaction();
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('start_time','end_time','min_cast_all','max_cast_all'));
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //投资总金额
    private function rule_castname($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'name'=>'required',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        if(!isset($request->stage_name)){
            $request->stage_name = null;
        }
        DB::beginTransaction();
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('name','stage_name'));
        $rule->save();
        if($rule->id){
            DB::commit();
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //是否邀请
    private function rule_invite($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'is_invite' => 'required|in:0,1',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('is_invite',));
        $rule->save();
        if($rule->id){
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //邀请人数
    private function rule_invitenum($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'min_invitenum' => 'required|integer',
            'max_invitenum' => 'required|integer',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('min_invitenum','max_invitenum'));
        $rule->save();
        if($rule->id){
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //用户等级
    private function rule_userlevel($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'min_level' => 'required',
            'max_level' => 'required',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('min_level','max_level'));
        $rule->save();
        if($rule->id){
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //用户积分
    private function rule_usercredit($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'min_credit' => 'required|integer',
            'max_credit' => 'required|integer',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('min_credit','max_credit'));
        $rule->save();
        if($rule->id){
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //用户余额
    private function rule_balance($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'min_balance' => 'required|numeric',
            'max_balance' => 'required|numeric',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('min_balance','max_balance'));
        $rule->save();
        if($rule->id){
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //用户回款
    private function rule_payment($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'min_payment' => 'required|numeric',
            'max_payment' => 'required|numeric',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('min_payment','max_payment'));
        $rule->save();
        if($rule->id){
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //投资标类型
    private function rule_casttype($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'type' => 'required|integer',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('type'));
        $rule->save();
        if($rule->id){
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //活动参与人数
    private function rule_joinnum($type,$request){
        $validator = Validator::make($request->all(), [
            'activity_id'=>'required|alpha_num|exists:activities,id',
            'join_max' => 'required|integer',
        ]);
        if($validator->fails()){
            return array('error_code'=>10001,'error_msg'=>$validator->errors()->first());
        }
        $rule = new Rule();
        $rule->activity_id = $request->activity_id;
        $rule->rule_type = $type;
        $rule->rule_info = $this->Params2json($request,array('join_max'));
        $rule->save();
        if($rule->id){
            return array('error_code'=>0,'insert_id'=>$rule->id);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    private function getStorageTypeByName($type_name){
        $rules = config('activity.rule_child');
        foreach($rules as $key=>$val){
            if($val['model_name'] == ucfirst($type_name)){
                return $key;
            }
        }
    }


    private function Params2json($request,$params){
        $arr = array();
        foreach($params as $val){
            $arr[$val] = trim($request->$val);
        }
        return json_encode($arr);
    }


    /**
     * 添加奖品映射关系
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postAwardAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'award_type' => 'required|integer|min:1',
            'activity_id' => 'required|integer|min:1',
            'award_id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //奖品类型
        $data['award_type'] = intval($request->award_type);
        //活动ID
        $data['activity_id'] = intval($request->activity_id);
        //优惠券id
        $data['award_id'] = intval($request->award_id);
        //查看是否重复
        $count = Award::where($data)->count();
        if($count > 0){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'已经有该数据'));
        }
        $data['priority'] = isset($request->priority) ? intval($request->priority) : 0;
        $data['created_at'] = date("Y-m-d H:i:s");
        $data['updated_at'] = date("Y-m-d H:i:s");
        $awardID = Award::insertGetId($data);
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
        //活动ID
        $where['activity_id'] = intval($request->activity_id);
        if(empty($where['activity_id'])){
            return $this->outputJson(PARAMS_ERROR,array('activity_id'=>'活动id不能为空'));
        }
        $list = Award::where($where)->orderBy('updated_at','desc')->get()->toArray();
        foreach($list as &$item){
            $table = $this->_getAwardTable($item['award_type']);
            $name = $table::where('id',$item['award_id'])->select('name')->get()->toArray();
            if(count($name) >= 1 && isset($name[0]['name'])){
                $item['name'] = $name[0]['name'];
            }else{
                $item['name'] = '';
            }
        }
        return $this->outputJson(0,$list);
    }
    /**
     * 删除奖品映射关系
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postAwardDelete(Request $request){
        //记录id
        $where['id'] = intval($request->id);
        if(empty($where['id'])){
            return $this->outputJson(PARAMS_ERROR,array('id'=>'记录id不能为空'));
        }
        $status = Award::where($where)->delete();
        if($status){
            return $this->outputJson(0,array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }
    /**
     * 邀请人奖品映射关系添加
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postAwardInviteAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'award_type' => 'required|integer|min:1',
            'activity_id' => 'required|integer|min:1',
            'award_id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //奖品类型
        $data['award_type'] = intval($request->award_type);
        //活动ID
        $data['activity_id'] = intval($request->activity_id);
        //优惠券id
        $data['award_id'] = intval($request->award_id);
        //查看是否重复
        $count = AwardInvite::where($data)->count();
        if($count > 0){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'已经有该数据'));
        }
        $data['priority'] = isset($request->priority) ? intval($request->priority) : 0;
        $data['created_at'] = date("Y-m-d H:i:s");
        $data['updated_at'] = date("Y-m-d H:i:s");
        $awardID = AwardInvite::insertGetId($data);
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
    function postAwardInviteList(Request $request){
        //活动ID
        $where['activity_id'] = intval($request->activity_id);
        if(empty($where['activity_id'])){
            return $this->outputJson(PARAMS_ERROR,array('activity_id'=>'活动id不能为空'));
        }
        $list = AwardInvite::where($where)->orderBy('updated_at','desc')->get()->toArray();
        foreach($list as &$item){
            $table = $this->_getAwardTable($item['award_type']);
            $name = $table::where('id',$item['award_id'])->select('name')->get()->toArray();
            if(count($name) >= 1 && isset($name[0]['name'])){
                $item['name'] = $name[0]['name'];
            }else{
                $item['name'] = '';
            }
        }
        return $this->outputJson(0,$list);
    }
    /**
     * 删除奖品映射关系
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postAwardInviteDelete(Request $request){
        //记录id
        $where['id'] = intval($request->id);
        if(empty($where['id'])){
            return $this->outputJson(PARAMS_ERROR,array('id'=>'记录id不能为空'));
        }
        $status = AwardInvite::where($where)->delete();
        if($status){
            return $this->outputJson(0,array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }
    /**
     * 获取表对象
     * @param $awardType
     * @return Award1|Award2|Award3|Award4|Award5|Award6|bool
     */
    function _getAwardTable($awardType){
        if($awardType >= 1 && $awardType <= 6) {
            if ($awardType == 1) {
                return new Award1;
            } elseif ($awardType == 2) {
                return new Award2;
            } elseif ($awardType == 3) {
                return new Award3;
            } elseif ($awardType == 4) {
                return new Award4;
            } elseif ($awardType == 5) {
                return new Award5;
            } elseif ($awardType == 6){
                return new Coupon;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 活动参与列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityJoinsList(Request $request){
        $data = Func::Search($request,new ActivityJoin());
        return $this->outputJson(0,$data);
    }
    /**
     * 奖品发送记录列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSendRewardLogList(Request $request){
        $data = Func::Search($request,new SendRewardLog());
        return $this->outputJson(0,$data);
    }
    /**
     * 奖品发送记录列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBatchAwardList(Request $request){
        $data = Func::Search($request,new AwardBatch());
        return $this->outputJson(0,$data);
    }
}
