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
            'award_type' => 'required|integer|min:1',
            'award_id' => 'required|integer|min:1',
            'number' => 'required|integer|min:1',
            'expire_time' => 'date'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //判断是否添加过该名称
        $count = RedeemAward::where('name',$request->name)->count();
        if($count > 0){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该信息已经添加'));
        }
        //添加到关系表
        $data = array();
        $data['name'] = $request->name;
        $data['award_type'] = $request->award_type;
        $data['award_id'] = $request->award_id;
        $data['number'] = $request->number;
        $data['expire_time'] = $request->expire_time;
        $data['status'] = 0;
        $data['created_at'] = date("Y-m-d H:i:s");
        $insertID = RedeemAward::insertGetId($data);
        //放入队列（插入兑换码）
        $this->dispatch(new RedeemCodeCreate($insertID,$data['number']));
        return $this->outputJson(0,array('error_msg'=>'添加成功'));
    }
    /**
     * 兑换码查看
     * @param Request $request
     */
    public function getList(Request $request){
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
