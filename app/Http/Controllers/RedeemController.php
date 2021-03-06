<?php

namespace App\Http\Controllers;

use App\Jobs\RedeemCodeCreate;
use App\Jobs\RedeemExport;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\RedeemAward;
use App\Models\RedeemCode;
use Validator;
use Response;
class RedeemController extends Controller
{
    /**
     * 兑换码添加
     * @param Request $request
     */
    public function postAdd(Request $request){
        //验证必填项
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'type' => 'required|min:0',
            'award_type' => 'required|integer|min:1',
            'award_id' => 'required|integer|min:1',
            'number' => 'required|integer|min:1',
            'expire_time' => 'required|date'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //添加到关系表
        $data = array();
        $data['type'] = intval($request->type);
        $data['name'] = trim($request->name);
        $data['award_type'] = $request->award_type;
        $data['award_id'] = $request->award_id;
        $data['number'] = $request->number;
        $data['expire_time'] = $request->expire_time;
        //短信模板
        $data['message'] = $request->message;
        //站内信模板
        $data['mail'] = $request->mail;
        $data['status'] = $data["type"] == 1 ? 2 : 0;
        $data['created_at'] = date("Y-m-d H:i:s");
        //判断是否添加过该名称
        $count = RedeemAward::where('name',$data['name'])->where("type",1)->count();
        if($count > 0){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该信息已经添加'));
        }
        $insertID = RedeemAward::insertGetId($data);
        //放入队列（插入兑换码）
        if($data["type"] != 1){
            $this->dispatch(new RedeemCodeCreate($insertID,$data['number']));
        }
        return $this->outputJson(0,array('error_msg'=>'添加成功'));
    }
    /**
     * 兑换码关系列表
     * @param Request $request
     */
    public function getList(Request $request){
        $type = intval($request->type);
        $list = RedeemAward::where('type',$type)->orderby('id', 'desc')->paginate(20);
        return $this->outputJson(0,$list);
    }
    /**
     * 兑换码查看
     * @param Request $request
     */
    public function getCodeList(Request $request){
        //验证必填项
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $where['rel_id'] = $request->id;
        $list = RedeemCode::where($where)->paginate(20);
        return $this->outputJson(0,$list);
    }
    /**
     * 兑换码导出
     * @param Request $request
     */
    public function getExport(Request $request){
        //验证必填项
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $where['id'] = $request->id;
        $names = RedeemAward::where($where)->select('name')->get()->toArray();
        if(empty($names)){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该数据不存在'));
        }
        $name = isset($names[0]['name']) && !empty($names[0]['name']) ? $names[0]['name'] : 'default';
        $this->dispatch(new RedeemExport($request->id,$name));
        //修改导出状态为正在导出
        RedeemAward::where('id',$request->id)->update(array('export_status'=>1));
        return $this->outputJson(0,array('error_msg'=>'导出成功'));
    }
    public function getDownload(Request $request){
        //验证必填项
        $validator = Validator::make($request->all(), [
            'file' => 'required|min:2|max:255',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        return Response::download(base_path()."/storage/exports/{$request->file}");
    }
}
