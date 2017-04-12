<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use Lib\JsonRpcClient;
use App\Service\Func;
use App\Models\PoBaiYi;
use Validator, Request, Cache, DB, Session;

class PoBaiYiJsonRpc extends JsonRpc
{
    /**
     * 获取最近参与数据
     *
     * @JsonRpcMethod
     */
    public function pobaiyiList() {
        $list = Cache::remember('pobaiyi_list', 2, function() {
            $result = [];
            $list = PoBaiYi::select('user_id', 'award_name')->where('amount', '<', 25)->orderBy('id', 'desc')->take(5)->get();
            foreach ($list as $item) {
                if (!empty($item) && isset($item['user_id']) && !empty($item['user_id'])) {
                    $award = [];
                    $phone = Func::getUserPhone($item['user_id']);
                    $award['award_name'] = $item['award_name'];
                    $award['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                    $result[] = $award;
                }
            }
            $list2 = PoBaiYi::select('user_id', 'award_name')->where('amount', '>', 25)->orderBy('id', 'desc')->take(15)->get();
            foreach ($list2 as $item) {
                if (!empty($item) && isset($item['user_id']) && !empty($item['user_id'])) {
                    $award = [];
                    $phone = Func::getUserPhone($item['user_id']);
                    $award['award_name'] = $item['award_name'];
                    $award['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                    $result[] = $award;
                }
            }
            shuffle($result);
            return $result;
        });

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        );
    }
}
