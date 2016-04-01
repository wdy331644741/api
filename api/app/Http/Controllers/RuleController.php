<?php

namespace App\Http\Controllers;

use App\Models\Rule;
use App\Models\Rule\Cast;
use App\Models\Rule\Channel;
use App\Models\Rule\Invite;
use App\Models\Rule\Register;
use App\Models\Rule\Userlevel;
use Illuminate\Http\Request;

use App\Http\Requests;

class RuleController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getAdd(Request $request,$type)
    {
        $model_name = config('activity.rule_child.'.$type.'.model_name');
        switch ($type){
            case 0:
                $this->rule_register($type,$request,$model_name);
                break;
            case 1:
                $this->rule_channel($type,$request,$model_name);
                break;
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
    }


    private function rule_register($type,$request,$model_name){
        $validator = Validator::make($request->all(), [
            'min_time' => 'required|date',
            'max_time' => 'required|dete',
        ]);
        if($validator->fails()){
            return $this->outputJson(10000,$validator->errors());
        }
        DB::beginTransaction();
        $obj = new $model_name;
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
                return $this->outputJson(0,array('error_msg'=>'ok'));
            }else{
                DB::rollback();
                return $this->outputJson(10004,array('error_msg'=>'Insert Failed!'));
            }
        }else{
            return $this->outputJson(10004,array('error_msg'=>'Insert Failed!'));
        }
    }

    private function rule_channel($type,$request,$model_name){
        $validator = Validator::make($request->all(), [
            'channels' => 'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10000,$validator->errors());
        }
        $obj = new $model_name;
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
                return $this->outputJson(0,array('error_msg'=>'ok'));
            }else{
                DB::rollback();
                return $this->outputJson(10004,array('error_msg'=>'Insert Failed!'));
            }
        }else{
            return $this->outputJson(10004,array('error_msg'=>'Insert Failed!'));
        }
    }
}
