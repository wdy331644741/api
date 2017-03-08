<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Tzyxj;
use App\Models\TzyxjUniquRecord;
use App\Service\ActivityService;
use Lib\JsonRpcClient;
use App\Service\Func;
use Validator, Config, Request, Cache, DB, Session;
use Illuminate\Pagination\Paginator;

class TzyxjJsonRpc extends JsonRpc
{
    /**
     * 活动详情
     *
     * @JsonRpcMethod
     */
    public function tzyxjInfo() {
        $config = config('tzyxj');
        $currentWeek = date('W');

        $info = [
            'enable' => 0,
            'week' => 0,
            'endSeconds' => 0,
            'award_size' => $config['award_size'],
            'award_list' => [],
        ];

        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $info['enable'] = 1;
        }

        // 计算当前周数
        $searchRes =  array_search($currentWeek, $config['weeks']);
        if($searchRes !== false) {
            $info['week'] = $searchRes +1;
        }

        // 活动结束秒数
        $todayEndSeconds = strtotime(date('Y-m-d 23:59:59')) - time();
        if(date('w') === 0 ) {
            $info['endSeconds'] = $todayEndSeconds;
        }else {
            $info['endSeconds'] = $todayEndSeconds + 3600*24 * (7-date('w'));
        }

        // 获奖历史
        $info['award_list'] = $this->getAwardList($config, $currentWeek);


        return [
            'code' => 0,
            'message' => 'success',
            'data' => $info
        ];
    }

    /**
     * 活动排行
     *
     * @JsonRpcMethod
     */
    public function tzyxjRank($params) {
        if(empty($params->min) || empty($params->max) || empty($params->page)) {
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }

        $page = $params->page;
        $perPage = isset($params->per_page) ? $params->per_page : 10;

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $result = TzyxjUniquRecord::select('user_id', 'updated_at', 'amount')->where(['number' => 1, 'week' => date('W')])->where('amount',  '>=',  $params->min)->where('amount', '<=', $params->max)->orderBy('updated_at', 'asc')->paginate($perPage)->toArray();
        $data = &$result['data'];
        if($data) {
            foreach($data as &$value) {
                $phone = Func::getUserPhone($value['user_id']);
                $value['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result
        ];
    }

    /**
     * 获取投资记录
     *
     * @JsonRpcMethod
     */
    public function tzyxjRecord($params) {
        global $userId;

        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        if(empty($params->page)) {
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $page = $params->page;

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $result = Tzyxj::select('amount', 'updated_at')->where(['user_id'=> $userId, 'week' => date('W')])->orderBy('created_at', 'asc')->paginate(10)->toArray();
        $data = &$result['data'];
        if($data) {
            foreach($data as &$value) {
                $item = TzyxjUniquRecord::select('number')->where(['amount' => $value['amount'], 'week' => date('W')])->first();
                if($item && $item['number'] > 1) {
                    $value['is_unique']  = 0;
                } else {
                    $value['is_unique']  = 1;
                }
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result
        ];
    }

    /**
     * 获取推荐数值
     *
     * @JsonRpcMethod
     */
    public function tzyxjRecomment() {
        $config = config('tzyxj');
        $uniqueRecNum = $config['unique_rec_num'];
        $rangeList = [];
        $recList = [];
        foreach($config['range_list'] as $range) {
            $rangeList[] = TzyxjUniquRecord::select('amount')->where(['number' => 0, 'week' => date('W')])->where('amount', '>=', $range['min'])->where('amount',  '<=', $range['max'])->inRandomOrder()->take(8)->get();
        }
        for($i =0; $i <$uniqueRecNum; $i++) {
            foreach($rangeList as $range) {
                if(isset($range[$i])) {
                    $recList[] = $range[$i]['amount'];
                }
                if(count($recList) >= $uniqueRecNum) {
                    break 2;
                }
            }
        }
        sort($recList);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $recList
        ];
    }

    /**
     * 获取唯一值列表
     *
     * @JsonRpcMethod
     */
    public function tzyxjUniqueList() {
        $uniqueList = TzyxjUniquRecord::select('user_id', 'amount')->where(['number' => 1, 'week' => date('W')])->orderBy('updated_at', 'asc')->take(50)->get();
        if($uniqueList) {
            foreach($uniqueList as &$item) {
                $item['phone'] = Func::getUserPhone($item['user_id']);
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $uniqueList,
        ];
    }

    /**
     * 初始化
     *
     * @JsonRpcMethod
     */
    public function tzyxjInit($params) {
        if($params->key !== 'wanglijinrong2017') {
            return ;
        }
        $config = config('tzyxj');
        $amountMaxSize = $config['amount_max_size'];
        $y = 0;
        $insertArr = [];
        foreach($config['weeks'] as $week) {
            $res = TzyxjUniquRecord::select('amount')->where('week', $week)->get();
            $amounts = [];
            foreach($res as $value) {
                $amounts[$value['amount']] = 1;
            }
            for($i = 100; $i <= $amountMaxSize; $i += 100) {
                if(isset($amounts[$i])) {
                    continue;
                }
                $insertArr[] = [
                    'week' => $week,
                    'amount' => $i,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $y++;
            }
        }
        TzyxjUniquRecord::insert($insertArr);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $y,
        ];
    }


    /**
     * 获奖历史
     *
     * @param $config
     * @param $currentWeek
     * @return mixed
     */
    private function getAwardList($config, $currentWeek) {
        return Cache::remember('tzyxj_list', 10, function() use ($config, $currentWeek) {
            $data = [];
            foreach($config['weeks'] as $key => $value) {
                if($value == $currentWeek) {
                    break;
                }
                $res = TzyxjUniquRecord::where(array('week' => $value, 'number' => 1))->orderBy('updated_at', 'desc')->first();
                if($res) {
                    $phone = Func::getUserPhone($res['user_id']);
                    $data[] = ['phone' => $phone, 'award_size' => $config['award_size']];
                }

            }
            return $data;
        });
    }

}

