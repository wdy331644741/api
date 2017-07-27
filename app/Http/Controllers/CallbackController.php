<?php

namespace App\Http\Controllers;

use App\Models\LifePrivilege;
use App\Service\Func;
use App\Service\SendAward;
use Illuminate\Http\Request;
use App\Http\Requests;
use Config;

class CallbackController extends Controller
{
    public function postFeeAndFlowCallback(Request $request){
        //code    1成功9失败
        $ret_code = isset($request->ret_code) && !empty($request->ret_code) ? $request->ret_code : '';
        //订单id
        $sporder_id = isset($request->sporder_id) && !empty($request->sporder_id) ? $request->sporder_id : '';
        //回调时间
        $ordersuccesstime = isset($request->ordersuccesstime) && !empty($request->ordersuccesstime) ? $request->ordersuccesstime : '';
        //错误信息
        $err_msg = isset($request->err_msg) && !empty($request->err_msg) ? $request->err_msg : '';
        if(empty($ret_code) || empty($sporder_id)){
            return false;
        }
        $thisOrder = LifePrivilege::where('order_id',$sporder_id)->first();
        if(isset($thisOrder['order_status'])){
            $upData = [];
            $upData['order_status'] = 0;
            $upData['order_time'] = $ordersuccesstime;
            $upData['order_remark'] = $err_msg;
            if($ret_code == 1){
                //成功
                $upData['order_status'] = 3;
            }
            if($ret_code == 9){
                //失败
                $upData['order_status'] = 2;
            }
            //如果接口调用失败而回调成功  则为异常状态
            if($thisOrder['order_status'] == 2 && $ret_code == 1){
                $upData['order_status'] = 4;//异常状态
            }
            //如果接口调用成功而回调失败  则为异常状态
            if($thisOrder['order_status'] == 3 && $ret_code == 9){
                $upData['order_status'] = 4;//异常状态
            }
            LifePrivilege::where('order_id',$sporder_id)->update($upData);
            return 1;
        }
        return 0;
    }
}
