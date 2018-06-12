<?php

namespace App\Http\Controllers;

use App\Jobs\WorldCupSendAward;
use App\Service\WorldCupService;
use Illuminate\Http\Request;

use App\Http\Requests;
use Validator, Excel, Response;

class WorldCupController extends Controller
{

    const DIVIDE_CASH = 10000000;
    public function getSendAward($type, $amount) {
        $params['type'] = $type;
        $params['amount'] = $amount;
        $validator = Validator::make($params, [
            'type' => 'required|integer',
            'amount' => 'required|integer',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $data = WorldCupService::getTotalBallList();
        if (empty($data)) {
            return $this->outputJson(10001,array('error_msg'=> '发奖列表为空或已发奖完成'));
        }
        // 现金= 我的总进球数 * （1000000 / 活动用户支持球队总进球数之和）
        foreach ($data as &$val) {
            $val['size'] = bcdiv(bcmul($val['number'], self::DIVIDE_CASH ), $amount, 2);
            unset($val['number']);

        }
        // type=1 生成excel
        if ($type == 1) {
            $fileName = date("YmdHis") . mt_rand(1000, 9999);
            $typeName = "xls";
            array_unshift($data, array('userId', 'size'));
            Excel::create($fileName,function($excel) use ($data){
                $excel->sheet('score', function($sheet) use ($data){
                    $sheet->rows($data);
                });
            })->store($typeName);
            return Response::download(storage_path()."/exports/" . $fileName . "." . $typeName);
            // type = 2 生成发奖队列
        } else if ($type == 2) {
            foreach ($data as $val) {
                $this->dispatch((new WorldCupSendAward($val['user_id'], $val['size']))->onQueue('worldcup'));
            }
            return $this->outputJson(0);
        }
        return $this->outputJson(10001,array('error_msg'=> '参数值不正确'));
    }

    public function messages()
    {
        return [
            'type.required' => 'A title is required',
            'amount.required'  => 'A message is required',
        ];
    }
}
