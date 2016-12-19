<?php

namespace App\Http\Controllers;

use App\Service\MoneyShareBasic;
use Illuminate\Http\Request;
use App\Service\Func;
use App\Models\MoneyShare;
use App\Jobs\ReissueMoneyShare;
use Validator;

class MoneyShareController extends Controller
{
    /**
     * 商品添加&修改
     */
    function postOperation(Request $request){
        $mall_id = intval($request->id);
        $validator = Validator::make($request->all(), [
            'award_type' => 'required|integer|min:1',
            'award_id' => 'required|integer|min:1',
            'total_money' => 'required|integer|min:1',
            'total_num' => 'required|integer|min:1',
            'min' => 'required|integer|min:1',
            'max' => 'required|integer|min:1',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //积分值
        $data['blessing'] = $request->blessing;
        //说明
        $data['user_name'] = $request->user_name;
        //总数量
        $data['award_type'] = $request->award_type;
        //总数量
        $data['award_id'] = $request->award_id;
        //总数量
        $data['total_money'] = $request->total_money;
        //总数量
        $data['total_num'] = $request->total_num;
        //总数量
        $data['min'] = $request->min;
        //总数量
        $data['max'] = $request->max;
        //开始时间
        $data['start_time'] = $request->start_time;
        //结束时间
        $data['end_time'] = $request->end_time;
        //判断是添加还是修改
        if($mall_id != 0){
            //查询该信息是否存在
            $where = array();
            $where['id'] = $mall_id;
            $isExist = MoneyShare::where($where)->count();
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = MoneyShare::where('id',$mall_id)->update($data);
                if($status){
                    return $this->outputJson(0, array('error_msg'=>'修改成功'));
                }else{
                    return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'修改失败'));
                }
            }else{
                return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
            }
        }else{
            //红包标示
            $data['identify'] = Func::randomStr(15);
            //添加时间
            $data['created_at'] = date("Y-m-d H:i:s");
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            $id = MoneyShare::insertGetId($data);
            return $this->outputJson(0, array('insert_id'=>$id));
        }
    }
    /**
     * 商品列表
     */
    function getList(Request $request){
        $request->data = array('order'=>array("id" => "desc"));
        $data = Func::Search($request,new MoneyShare());
        return $this->outputJson(0,$data);
    }
    /**
     * 商品上线
     */
    function postUpStatus(Request $request){
        $mall_id = intval($request->id);
        $where = array();
        $where['id'] = $mall_id;
        $data = MoneyShare::where($where)->select('status')->first();
        if(empty($data) && !isset($data['status'])){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
        }
        //上线修改
        $status = MoneyShare::where('id',$mall_id)->update(array('status'=>1,'updated_at'=>date("Y-m-d H:i:s")));
        if($status){
            return $this->outputJson(0, array('error_msg'=>'上线成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'上线失败'));
        }
    }
    /**
     * 商品下线
     */
    function postDownStatus(Request $request){
        $mall_id = intval($request->id);
        $where = array();
        $where['id'] = $mall_id;
        $data = MoneyShare::where($where)->select('status')->first();
        if(empty($data) && !isset($data['status'])){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
        }
        //下线修改
        $status = MoneyShare::where('id',$mall_id)->update(array('status'=>0,'updated_at'=>date("Y-m-d H:i:s")));
        if($status){
            return $this->outputJson(0, array('error_msg'=>'下线成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'下线失败'));
        }
    }
    /**
     * 商品删除
     */
    function postDelete(Request $request){
        $mall_id = intval($request->id);
        $where = array();
        $where['id'] = $mall_id;
        $data = MoneyShare::where($where)->select('status')->first();
        if(empty($data) && !isset($data['status'])){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
        }
        //删除修改
        $oneYuan = MoneyShare::find($mall_id);
        $res = $oneYuan->delete();
        if($res){
            return $this->outputJson(0, array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }
    /**
     * 一键补发奖品
     *
     * @param Request $request
     * @return json
     */
    function postReissueAward() {
        $this->dispatch(new ReissueMoneyShare());
        return $this->outputJson(0, array('error_msg'=>'成功'));
    }
}
