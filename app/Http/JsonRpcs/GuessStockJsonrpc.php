<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Http\Controllers\Controller;
use App\Models\HdPertenGuess;
use App\Models\HdPertenGuessLog;
use App\Models\HdPertenStock;
use App\Models\HdWeeksGuessLog;
use App\Service\PerBaiService;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Config, Request, Cache,DB;

class GuessStockJsonrpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function stockInfo() {
        global $userId;
        $result = [
            'login' => 0,
            'available' => 0,
            'award' => 0,
            'countdown' => 0,
            'up' => 0,
            'down' => 0,
        ];
        if ( !empty($userId) ) {
            $result['login'] = 1;
        }
        $time = time();
        // 活动配置信息
        $activity = PerBaiService::getActivityInfo();
        if ( $activity ) {
            if ( $time > strtotime($activity['start_time']) ) {
                $result['available'] = 1;
            }
            $result['award'] = $activity['guess_award'];
            $date = date("Y-m-d");
            if ( $date )
            $h = date('Hi');
            //        T-1日15:30-T日13:00  预言T日涨跌
//T日13:00-T日15:30  停止预言，等待开奖
//T日15:30-T+1日13:00  预言T+1日涨跌
            if ( $h < 1300 ) {
                $result['countdown'] = strtotime(date('Y-m-d 13:00:00')) - $time;
            }
            if ( $h >= 1530) {
                $result['countdown'] = strtotime(date('Y-m-d 13:00:00', strtotime('+1 day'))) - $time;
            }
        }
        $guess = HdPertenGuess::select('sum(number) total')->where(['status'=>0, 'period'=>$activity['id']])->groupBy('type')->get()->toArray();
        foreach ($guess as $v) {
            if ($v['type'] == 1) {
                $result['up'] = $v['total'];
            }
            if ($v['type'] == 2) {
                $result['down'] = $v['total'];
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 股指指数
     *
     * @JsonRpcMethod
     */
    public function stockNumber() {
        $acvitity = PerBaiService::getActivityInfo();
        $date = date('Y-m-d');
        $w = date('w');
        $h = date('Hi');
        if ( in_array($date, PerBaiService::getStockClose()) || $w == 6 || $w == 0 || $h < 930 ) {
            $stock = HdPertenStock::where(['period'=>$acvitity['id']])->orderBy('id', 'desc')->first();
            $return['time'] = $stock['curr_time'];
            $return['change'] = $stock['change'];
            $return['stock'] = $stock['stock'];
        } else {
            $flag = true;
            if ($h > 1530) {
                $stock = HdPertenStock::where(['curr_time'=>$date, 'period'=>$acvitity['id']])->first();
                if ($stock) {
                    $return['time'] = $stock['curr_time'];
                    $return['change'] = $stock['change'];
                    $return['stock'] = $stock['stock'];
                    $flag = false;
                }
            }
            if ($flag) {
                $stock = PerBaiService::getStockPrice();
                $return['time'] = $date;
                $return['stock'] = round($stock[0], 2);
                $return['change'] = round($stock[1], 2);
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $return,
        ];
    }

    /**
     * 竞猜
     *
     * @JsonRpcMethod
     */
    public function stockDraw($params)
    {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $activity = PerBaiService::getActivityInfo();
        if( !$activity || time() < $activity['start_time']) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $type = isset($params->type) ? intval($params->type) : 0;//1涨/2跌
        $number = isset($params->num) ? intval($params->num) : 0;
        if( !in_array($type, [1,2]) || $number <= 0){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        DB::beginTransaction();
        $userAttr = Attributes::getItemLock($userId, PerBaiService::$guessKeyUser);
        $user_num = isset($userAttr->number) ? $userAttr->number : 0;
        if ( $number > $user_num) {
            DB::rollBack();
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        $insert = HdPertenGuess::create([
            'user_id'=>$userId,
            'period'=>$activity['id'],
            'type'=>$type,
            'number'=>$number,
            'status'=>0,
        ]);
        if (!$insert) {
            DB::rollBack();
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $userAttr->number -= $number;
        $userAttr->save();
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => true,
        ];
    }

    /**
     * 列表
     *
     * @JsonRpcMethod
     */
    public function stockGuessList() {
        $activity = PerBaiService::getActivityInfo();
        $data = HdWeeksGuessLog::selectRaw("user_id, sum(money) m")->where('period', $activity['id'])->groupBy('user_id')->orderBy('m', 'desc')->limit(50)->get()->toArray();
        foreach ($data as $k=>$v) {
            $phone = Func::getUserPhone($v['user_id']);
            $data[$k]['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 竞猜记录
     *
     * @JsonRpcMethod
     */
    public function weeksGuessRecord() {
        global $userId;
        if (!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $activity = PerBaiService::getActivityInfo();
        $period = $activity['id'];
        $data = Cache::remeber('perten_guess' . $userId, 10, function() use ($userId, $period) {
            $list = HdPertenStock::select(['curr_time', 'stock', 'change'])->where(['period'=>$period])->orderBy('id', 'desc')->get()->toArray();
            foreach ($list as $k=>$v) {
                $list[$k]['up'] = HdPertenGuess::selectRaw("sum(number) total")->where(['period'=>$period, 'user_id'=>$userId, 'type'=>1])->whereRaw(" to_days(created_at) = {$v['curr_time']}")->value('total');
                $list[$k]['down'] = HdPertenGuess::selectRaw("sum(number) total")->where(['period'=>$period, 'user_id'=>$userId, 'type'=>2])->whereRaw(" to_days(created_at) = {$v['curr_time']}")->value('total');
                $list[$k]['money'] = HdPertenGuessLog::where(['period'=>$period, 'user_id'=>$userId])->value('money');
        }
            return $list;
        });
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }
}

