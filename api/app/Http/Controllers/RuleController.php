<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Requests;
use App\Models\Rule;
/*use App\Models\Rule\Register;
use App\Models\Rule\Channel;
use App\Models\Rule\Invite;
use App\Models\Rule\Invitenum;
use App\Models\Rule\Userlevel;
use App\Models\Rule\Invite;
use App\Models\Rule\Invite;
use App\Models\Rule\Invite;
use App\Models\Rule\Invite;
use App\Models\Rule\Invite;*/


use Validator;

class RuleController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postAdd(Request $request,$model_name)
    {
        $type = $this->getStorageTypeByName($model_name);
        $func_name = 'rule_'.strtolower($model_name);
        $res = $this->$func_name($type,$request);
        if($res['insert_id']){
            return $this->outputJson(0,$res);
        }else{
            return $this->outputJson(10004,array('error_msg'=>'Insert Failed!'));
        }
    }

    //删除
    public function getDel($rule_id){
        if($rule_id){
            $rule_model = Rule::findOrFail($rule_id);
            Rule::destroy($rule_id);
            $type = $rule_model->rule_type;
            $res = $rule_model->getRuleByType($type)->delete();
            if($res){
                return $this->outputJson(0,array('error_msg'=>'ok'));
            }else{
                return $this->outputJson(10001,array('error_msg'=>'Delete Failed!'));
            }
        }
    }

    //获取rules
    public function getConfig(){
        $rules = config('activity.rule_child');
        $new_rules = array();
        foreach($rules as $key=>$val){
            unset($val['model_path']);
            $new_rules[$key] = $val;
        }
        return $this->outputJson(0,$new_rules);
    }

    //获取活动规则
    public function getRulebyid($activity_id){
        $rule_child = Rule::where('activity_id',$activity_id)->get();
        $rules = array();
        foreach($rule_child  as $val){
            switch($val->rule_id){
                case 0:
                    break;
                case 1:
                    break;
                case 2:
                    break;
                case 3:
                    break;
                case 4:
                    break;
                case 5:
                    break;
                case 6:
                    break;
                case 7:
                    break;
                case 8:
                    break;
            }
        }
        print_r($rules);exit;
    }

    private function rule_register($type,$request){
        $validator = Validator::make($request->all(), [
            'min_time' => 'required',
            'max_time' => 'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10000,$validator->errors());
        }
        DB::beginTransaction();
        $obj =  new Rule\Register();
        $obj->min_time = $request->min_time;
        $obj->max_time = $request->max_time;
        $obj->save();
        if($obj->id){
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
                return false;
            }
        }else{
            DB::rollback();
            return false;
        }
    }

    private function rule_channel($type,$request){
        $validator = Validator::make($request->all(), [
            'channels' => 'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10000,$validator->errors());
        }
        DB::beginTransaction();
        $obj = new Rule\Channel();
        $obj->channels = $request->channels;
        $obj->save();
        if($obj->id){
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
                return false;
            }
        }else{
            DB::rollback();
            return false;
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
