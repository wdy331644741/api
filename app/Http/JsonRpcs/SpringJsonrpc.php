<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\HdSpring;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Pagination\Paginator;
use Lib\JsonRpcClient;

use Config, Request, Cache,DB;

class SpringJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function springInfo() {
        global $userId;
        $result = [
                'login' => 0,
                'available' => 0,
                'join' => 0,
                'fund' => 0,
                ];
        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = 1;
        }
        $config = Config::get('spring');
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $result['available'] = 1; //活动开始
        }
        if ($result['login'] && $result['available']) {
            $join = Attributes::getItem($userId, $config['spring_join_key']);
            if ($join) {
                $result['join'] = 1;
                $fund = Attributes::getNumber($userId, $config['spring_drew_user']);
                $result['fund'] = is_null($fund) ? 0 : $fund;
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 点击参与活动
     *
     * @JsonRpcMethod
     */
    public function springJoin() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $join_key = Config::get('spring.spring_join_key');
        Attributes::setItem($userId, $join_key);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => true,
        ];
    }

    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function springList() {
        $data = HdSpring::select('user_id', 'name', 'created_at')->orderBy('id', 'desc')->limit(30)->get()->toArray();
        foreach ($data as $k=>$v){
            if(!empty($v['user_id'])){
                $phone = Func::getUserPhone($v['user_id']);
                $data[$k]['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 获取我的奖品列表
     *
     * @JsonRpcMethod
     */
    public function springMyList() {
        global $userId;
//        $num = isset($params->num) ? $params->num : 10;
//        $page = isset($params->page) ? $params->page : 1;
//        Paginator::currentPageResolver(function () use ($page) {
//            return $page;
//        });
//        if($num <= 0){
//            throw new OmgException(OmgException::API_MIS_PARAMS);
//        }
//        if($page <= 0){
//            throw new OmgException(OmgException::API_MIS_PARAMS);
//        }
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $data = HdSpring::select('name', 'created_at')
            ->where('user_id',$userId)
            ->orderBy('id', 'desc')->get()->toArray();
//        $rData['total'] = $data['total'];
//        $rData['per_page'] = $data['per_page'];
//        $rData['current_page'] = $data['current_page'];
//        $rData['last_page'] = $data['last_page'];
//        $rData['from'] = $data['from'];
//        $rData['to'] = $data['to'];
//        $rData['list'] = $data['data'];
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 兑换奖品（发奖）
     *
     * @JsonRpcMethod
     */
    public function springExchange($params) {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $type = isset($params->type) ? $params->type : '';
        $num = isset($params->num) ? $params->number : 0;
        if (!$type || !$num) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $config = Config::get('spring');
        $award = [];
        foreach ($config['awards'] as $v) {
            if ($v['alias_name'] == $type) {
                $award = $v;
            }
        }
        if (empty($award)) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        if (!Attributes::getItem($userId, $config['spring_join_key'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_JOIN);
        }
        //事务开始
        DB::beginTransaction();
        $attr = Attributes::getItemLock($userId, $config['spring_drew_user']);
        $fund = $attr->number;
        $award_fund = $award['fund'] * $num;
        if ($fund < $award_fund ) {
            DB::rollBack();
            throw new OmgException(OmgException::FUND_LACK_FAIL);
        }
        $model = new HdSpring();
        $model->user_id = $userId;
        $model->name = $award['name'];
        $model->alias_name = $award['alias_name'];
        $model->status = 1;
        //$model->number = $num;
        if (!$model->save()) {
            DB::rollBack();
            throw new OmgException(OmgException::DATABASE_ERROR);
        }
        Attributes::decrement($userId, $config['spring_drew_user'], $award_fund);
        Attributes::increment($userId, $config['spring_drew_total'],$award_fund);
        //事务提交结束
        DB::commit();
        unset($award['fund']);
        unset($award['alias_name']);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $award,
        ];
    }

}

