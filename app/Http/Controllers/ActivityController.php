<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Models\Activity;
use App\Models\Rule;
use App\Models\ActivityGroup;
use App\Models\Award;
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
    //
    public function postAdd(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'group_id'=>'required|exists:activity_groups,id',
            'start_at'=> 'date',
            'end_at' => 'date',
            'trigger_index'=>'required|integer',
            'trigger_type'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $activity = new Activity;
        $activity->name = $request->name;
        if($request->start_at){
            $activity->start_at = $request->start_at;
        }
        if($request->end_at){
            $activity->end_at  = $request->end_at;
        }
        $activity->group_id = $request->group_id;
        $activity->trigger_index = $request->trigger_index;
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
            'group_id'=>'required|exists:activity_groups,id',
            'start_at'=> 'date',
            'end_at' => 'date',
            'trigger_index'=>'required|integer',
            'trigger_type'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }

        $res = Activity::where('id',$request->id)->update([
            'name'=>$request->name,
            'group_id'=>$request->group_id,
            'start_at'=>$request->start_at,
            'end_at'=>$request->end_at,
            'trigger_index'=>$request->trigger_index,
            'trigger_type'=>$request->trigger_type,
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
    public function getGroupList(){
        $data = ActivityGroup::with('activities')->orderBy('id','desc')->paginate(20);
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
            $val->rule_info = json_decode($val->rule_info);
            $val->rule_type = strtolower($model_name);
            $rules[] = $val;
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
            'min_time' => 'date',
            'max_time' => 'date|after:min_time',
            'activity_id'=>'alpha_num|exists:activities,id',
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

    private function rule_channel($type,$request){
        $validator = Validator::make($request->all(), [
            'channels' => 'required',
            'activity_id'=>'alpha_num|exists:activities,id',
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


    private function Params2json($request,$params){
        $arr = array();
        foreach($params as $val){
            $arr[$val] = $request->$val;
        }
        return json_encode($arr);
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
    /**
     * 获取表对象
     * @param $awardType
     * @return Award1|Award2|Award3|Award4|Award5|Award6|bool
     */
    function _getAwardTable($awardType){
        if($awardType >= 1 && $awardType <= 7) {
            if ($awardType == 1) {
                return new Award1;
            } elseif ($awardType == 2) {
                return new Award2;
            } elseif ($awardType == 3) {
                return new Award2;
            } elseif ($awardType == 4) {
                return new Award3;
            } elseif ($awardType == 5) {
                return new Award4;
            } elseif ($awardType == 6) {
                return new Award5;
            } elseif ($awardType == 7){
                return new Coupon;
            }else{
                return false;
            }
        }else{
            return false;
        }
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
