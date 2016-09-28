<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use Lib\JsonRpcClient;
use Config;
use Excel;

class MarkController extends Controller
{
    /**
     * 果粉专享
     *
     * @param Request $request
     * @return json
     */
    function getMark(Request $request) {
        $param['projectName'] = $request->projectName;
        if(empty($param['projectName'])){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'散标名不能为空'));
        }
        $param['startTime'] = $request->startTime;
        if(empty($param['startTime'])){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'开始时间不能为空'));
        }
        $param['page'] = $request->page;
        $url = Config::get("mark.mark_http_url");
        $client = new JsonRpcClient($url);
        $list = $client->exclusiveStandard($param);
        if(isset($list['result']['code']) && $list['result']['code'] === 0){
            if(!empty($list['result']['data'])){
                $fileName = date("YmdHis").mt_rand(1000,9999)."_".$param['page'];
                $typeName = "xls";
                $cellData = $list['result']['data'];

                foreach($cellData as $key => $item){
                    if($key == 0){
                        $cellData[$key] = array('散标id','用户id','投资金额','期限','标类型','天标或月标','手机号');
                    }
                    if($item['type'] == 3){
                        $type = "月利宝";
                    }
                    if($item['type'] == 20){
                        $type = "散标";
                    }
                    if($item['scatter_type'] == 1){
                        $scatter_type = "天标";
                    }
                    if($item['scatter_type'] == 2){
                        $scatter_type = "月标";
                    }
                    $cellData[$key+1] = array($item['project_id'],$item['user_id'],$item['amount'],$item['term'],$type,$scatter_type,$item['mobile']);
                }
                Excel::create($fileName,function($excel) use ($cellData){
                    $excel->sheet('investmentList', function($sheet) use ($cellData){
                        $sheet->rows($cellData);
                    });
                })->store($typeName);
                return Response::download(base_path()."/storage/exports/".$fileName.'.'.$typeName);
            }else{
                return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'没有数据'));
            }
        }
        return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'没有数据'));
    }
}
