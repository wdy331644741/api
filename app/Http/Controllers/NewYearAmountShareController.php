<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Lib\JsonRpcClient;
use App\Http\Requests;
use App\Models\JsonRpc;
use App\Models\Hd19AmountShare;
use Validator,DB;
use Excel;

class NewYearAmountShareController extends Controller
{
    public function getTest(){
        $url = "http://api-omg.wanglibao.com/rpc";
        $client = new JsonRpcClient($url);
//        $res = $client->receiveAmount(array('shareCode' =>"111"));
        print_r($client);exit;
        return $this->outputJson(0,$res);
    }
    /**
     *获取统计数据
     */
    public function getStatistics() {
        $res = [];
        //获取每日总成本数据
        $amountByDay = Hd19AmountShare::select(
            'date',
            DB::raw("SUM(amount) * 2 AS total_amount"),
            DB::raw("SUM(amount) AS total_share_amount"),
            DB::raw("SUM(amount) AS total_receive_amount"),
            DB::raw("COUNT(DISTINCT share_user_id) as share_users"),
            DB::raw("COUNT(DISTINCT user_id) as receive_users"),
            DB::raw("COUNT(1) as counts")
        )->groupBy("date")->get()->toArray();
        //格式数据
        foreach($amountByDay as $value){
            $res[$value['date']]['date'] = $value['date'];//日期
            $res[$value['date']]['total_amount'] = $value['total_amount'];//分享当日总成本
            $res[$value['date']]['total_share_amount'] = $value['total_share_amount'];//分享人获得金额
            $res[$value['date']]['total_receive_amount'] = $value['total_receive_amount'];//领取人获得金额
            $res[$value['date']]['share_users'] = $value['share_users'];//分享总人数（去重）
            $res[$value['date']]['receive_users'] = $value['receive_users'];//领取人数（去重
            $res[$value['date']]['counts'] = $value['counts'];//分享总数（建立关系数）
        }
        //获取每日总成本数据
        $userByDay = Hd19AmountShare::select(
            'date',
            'receive_status',
            'user_status',
            DB::raw("SUM(amount) * 2 AS un_send_amount"),
            DB::raw("COUNT(DISTINCT user_id) as users")
        )->groupBy("date","user_status","receive_status")->get()->toArray();
        //格式数据
        foreach ($userByDay as $item){
            if(isset($item['users']) && isset($item['receive_status']) && isset($item['user_status'])){
                //当日绑卡并领取的总人数
                if(($item['user_status'] == 1 || $item['user_status'] == 2) && $item['receive_status'] >= 2) {
                    //当日绑卡并领取的总人数
                    if (isset($res[$item['date']]['bind_card_receive'])) {
                        $res[$item['date']]['bind_card_receive'] += $item['users'];
                    } else {
                        $res[$item['date']]['bind_card_receive'] = $item['users'];
                    }
                }else{
                    $res[$item['date']]['bind_card_receive'] = isset($res[$item['date']]['bind_card_receive']) ? $res[$item['date']]['bind_card_receive'] : 0;
                }
                //注册且开奖人数
                if($item['user_status'] == 1 && $item['receive_status'] >= 2) {
                    if (isset($res[$item['date']]['register_num'])) {
                        $res[$item['date']]['register_num'] += $item['users'];
                    } else {
                        $res[$item['date']]['register_num'] = $item['users'];
                    }
                }else{
                    $res[$item['date']]['register_num'] = isset($res[$item['date']]['register_num']) ? $res[$item['date']]['register_num'] : 0;
                }
                //绑卡且开奖人数
                if($item['user_status'] == 2 && $item['receive_status'] >= 2) {
                    //绑卡且开奖人数
                    if (isset($res[$item['date']]['bind_card_num'])) {
                        $res[$item['date']]['bind_card_num'] += $item['users'];
                    } else {
                        $res[$item['date']]['bind_card_num'] = $item['users'];
                    }
                }else{
                    $res[$item['date']]['bind_card_num'] = isset($res[$item['date']]['bind_card_num']) ? $res[$item['date']]['bind_card_num'] : 0;
                }
                //老用户开奖人数
                if($item['user_status'] == 3 && $item['receive_status'] >= 2) {
                    //老用户开奖人数
                    if (isset($res[$item['date']]['old_user_num'])) {
                        $res[$item['date']]['old_user_num'] += $item['users'];
                    } else {
                        $res[$item['date']]['old_user_num'] = $item['users'];
                    }
                }else{
                    $res[$item['date']]['old_user_num'] = isset($res[$item['date']]['old_user_num']) ? $res[$item['date']]['old_user_num'] : 0;
                }
                if($item['receive_status'] == 1){
                    //在途成本
                    if(isset($res[$item['date']]['un_send_amount'])){
                        $res[$item['date']]['un_send_amount'] += $item['un_send_amount'];
                    }else{
                        $res[$item['date']]['un_send_amount'] = $item['un_send_amount'];
                    }
                }
            }

        }
        $return = [];
        foreach($res as $items){
            $return[] = $items;
        }
        return $this->outputJson(0,$return);

    }
    /**
     * 导出未绑卡用户数据
     */
    public function getExport(){
        $unBindBankData = Hd19AmountShare::where("user_status","<",3)->where("receive_status",1)->select("id","user_id","date","created_at")->get()->toArray();
        $cellData = array();
        foreach($unBindBankData as $key => $item){
            if($key == 0){
                $cellData[$key] = array('记录ID','用户ID','日期','时间');
            }
            $cellData[$key+1] = array($item['id'],$item['user_id'],$item['date'],$item['created_at']);
        }
        Excel::create('19年新年现金分享未绑卡用户',function($excel) use ($cellData){
            $excel->sheet('19年新年现金分享未绑卡用户', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })->export('xls');
    }
}
