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
            'user_id'=>'required|integer',
            'head_img'=>'required|integer',
            'phone'=>'required',
            'nickname'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $user = new User();
        $user->user_id = $request->user_id;
        $headArr = config('headimg.admin');
        $user->head_img =  $headArr[$request->head_img];
        $user->phone = $request->phone;
        $user->nickname = $request->nickname;
        $user->isadmin = 1;
        $user->save();
        if($user->id){
            return $this->outputJson(0,array('insert_id'=>$user->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除机器人账户
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_users,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = User::find($request->id)->forceDelete();
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
            'head_img'=>'required|integer',
            'nickname'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putArr = [
            'head_img'=>config('headimg.admin.'.$request->head_img),
            'nickname'=>$request->nickname
        ];
        $res = User::find($request->id)->update($putArr);
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
        $data = User::select('nickname','phone','black_time')->where('isblack',1)->with('blacks')->paginate(20)->toArray();
        return $this->outputJson(0,$data);
    }

    //拉黑
    public function postToBlack(Request $request){
        $validator = Validator::make($request->all(), [
            'phone'=>'required|exists:bbs_users,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putArr = [
            'isblack'=>1,
            'black_time'=>date('Y-m-d H:i:s')
        ];
        $res = User::where('phone',$request->phone)->update($putArr);
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
        $res = User::find($request->id)->update($putArr);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
}
