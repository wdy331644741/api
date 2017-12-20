<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use phpDocumentor\Reflection\Types\Null_;
use Validator;
use App\Models\Bbs\User;
use App\Http\Traits\BasicDatatables;
use Config;

class UserController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','user_id','head_img','phone', 'nickname', 'isblack', 'created_at', 'isadmin'];
    protected $deleteValidates = [
        'id' => 'required|exists:bbs_users,id'
    ];
    protected $addValidates = [
        'user_id' => 'required',
        'phone' => "required|unique:bbs_users",
        'nickname'=>'required|unique:bbs_users'
    ];
    protected $updateValidates = [
        'id' => 'required|exists:bbs_users,id',
    ];

    function __construct() {
        $this->model = new User();
    }

    //添加机器人账户
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'head_img'=>'',
            'id'=>'required|exists:bbs_users,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putData = array('isadmin'=>1);
        if(isset($request->head_img)){
            $putData['head_img'] = $request->head_img;
        }
        $res = User::where('id',$request->id)->update($putData);
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
        $res = User::where('id',$request->id)->update(['isadmin'=>0]);
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
            'head_img'=>'required',
            'phone' => "required|unique:bbs_users,phone,".$request->id,
            'nickname'=>'required|unique:bbs_users,nickname,'.$request->id,
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = User::where('id',$request->id)->update($request->all());
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
        $data = User::where('isblack',1)->with('black')->paginate(20)->toArray();
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
