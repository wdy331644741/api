<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use phpDocumentor\Reflection\Types\Null_;
use Validator;
use App\Models\Bbs\User;
use Config;

class UserController extends Controller
{
    //添加机器人账户
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'head_img'=>'',
            'user_id'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putData = ['isadmin',1];
        if(isset($request->head_img)){
            $putData['head_img'] = $request->head_img;
        }
        $res = User::where('user_id',$request->user_id)->update($putData);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //移除机器人账户
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_users,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = User::where('id',$request->id)->update(['isadmin',0]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //修改机器人账户
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_users,id',
            'head_img'=>'integer',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putData = [];
        if(isset($request->head_img)){
            $headArr = config('headimg.admin');
            $putData['head_img'] = $headArr[$request->head_img];
        }
        if(isset($request->nickname)){
            $putData['nickname'] = $request->nickname;
        }
        if (empty($putData)){
            return $this->outputJson(10009,array('error_msg'=>'Not Changed'));
        }
        $res = User::where('id',$request->id)->update($putData);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //机器人账号列表
    public function getList(){
        $data = User::where('isadmin',1)->get();
        return $this->outputJson(0,$data);
    }


    //黑名单列表
    public function getBlackList(){
        $data = User::where('isblack',1)->with('blacks')->paginate(20)->toArray();
        return $this->outputJson(0,$data);
    }

    //拉黑
    public function postToBlack(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_users,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putArr = [
            'isblack'=>1,
            'black_time'=>date('Y-m-d H:i:s')
        ];
        $res = User::where('id',$request->id)->update($putArr);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //移除黑名单
    public function postToUser(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_users,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putArr = [
            'isblack'=>0,
            'black_time'=>NULL
        ];
        $res = User::where('id',$request->id)->update($putArr);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
}
