<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;

use App\Http\Requests;

use Validator;

class ChannelController extends Controller
{
    //渠道列表
    public function getList(){
        $data = Channel::orderBy('id','desc')->paginate(20);
        return $this->outputJson(0,$data);
    }

    //添加渠道
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'alias_name'=>'required|alpha_num',
            'pre'=>'alpha_num|unique:channels,pre',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $request->pre = $request->pre ? $request->pre : 'def';
        $is_exists = Channel::where(['pre'=>$request->pre,'alias_name'=>$request->alias_name])->count();
        if($is_exists){
            return $this->outputJson(10001,array('error_msg'=>'渠道别名不能重复'));
        }
        $channel = new Channel();
        $channel->name = $request->name;
        $channel->alias_name = $request->alias_name;
        $channel->pre = $request->pre;
        $res = $channel->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$channel->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除渠道
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:channels,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Channel::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //修改渠道
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:channels,id',
            'name' => 'required|min:2|max:255',
            'alias_name'=>'required|alpha_num',
            'pre'=>'alpha_num|unique:channels,pre'.$request->pre,
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $request->pre = $request->pre ? $request->pre : 'def';
        $is_exists = Channel::where(['pre'=>$request->pre,'alias_name'=>$request->alias_name])->count();
        if($is_exists>1){
            return $this->outputJson(10001,array('error_msg'=>'相同类渠道别名不能重复'));
        }

        $updata = [
            'name' => $request->name,
            'alias_name' => $request->alias_name,
            'pre'=>$request->pre,
        ];

        $res = Channel::where('id',$request->id)->update($updata);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //渠道详情
    public function getInfo($id){
        $get['id'] = $id;
        $validator = Validator::make($get, [
            'id'=>'required|exists:channels,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $data = Channel::find($id)->toArray();
        return $this->outputJson(0,$data);
    }
}
