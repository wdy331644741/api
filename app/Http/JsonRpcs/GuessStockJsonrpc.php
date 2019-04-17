<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Http\Controllers\Controller;
use App\Models\HdPertenGuess;
use App\Models\HdPertenGuessLog;
use App\Models\HdPertenStock;
use App\Models\HdWeeksGuessLog;
use App\Service\Attributes;
use App\Service\Func;
use App\Service\PerBaiService;
use function GuzzleHttp\Psr7\str;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Service\GlobalAttributes;

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
            'number'=>0,//预言注数
            'countdown' => 0,
            'up' => 0,
            'down' => 0,
            'alert'=>[],
        ];
        if ( !empty($userId) ) {
            $result['login'] = 1;
        }
        $time = time();
        //活动规则配置
        $actRule = GlobalAttributes::getItem('perten_config_actrule');
        $result['act_rule'] = $actRule->text;
        // 活动配置信息
        $activity = PerBaiService::getActivityInfo();
        if ( $activity ) {
            if ( $time > strtotime($activity['start_time']) ) {
                $result['available'] = 1;
            }
            $result['award'] = $activity['guess_award'];
            $date = $date1 = date('Y-m-d');
            $w = date('w', strtotime($date));
            if ($w == 6 || $w == 0 || in_array($date, PerBaiService::getStockClose()) ) {
                $date = $this->getStockNext($date);
            }
            $h = date('Hi');
            //        T-1日15:30-T日13:00  预言T日涨跌
//T日13:00-T日15:30  停止预言，等待开奖
//T日15:30-T+1日13:00  预言T+1日涨跌
            if ( $h < 1300 ) {
                $result['countdown'] = strtotime("{$date} 13:00:00") - $time;
            }
            if ( $h >= 1530) {
                $next_day = date('Y-m-d', strtotime('+1 day', strtotime($date1)));
                $next_w = date('w', strtotime($next_day));
                if ($next_w == 6 || $next_w == 0 || in_array($next_day, PerBaiService::getStockClose()) ) {
                    $next_day = $this->getStockNext($next_day);
                }
                $result['countdown'] = strtotime("$next_day 13:00:00") - $time;
            }
        }
        $guess = HdPertenGuess::selectRaw('sum(number) total,`type`')->where(['status'=>0, 'period'=>$activity['id']])->groupBy('type')->get()->toArray();
        if(count($guess) > 0){
            foreach ($guess as $v) {
                if ($v['type'] == 1) {
                    $result['up'] = $v['total'];
                }
                if ($v['type'] == 2) {
                    $result['down'] = $v['total'];
                }
            }
        }

        if ($result['login'] && $result['available']) {
            $period = $activity['id'];
            $guess_alert = HdPertenGuess::where(['status'=>1, 'user_id'=>$userId, 'period'=>$period])->orderBy('id', 'desc')->first();
            if ($guess_alert && $guess_alert->alert == 0) {
                $curr_time = date('Y-m-d', strtotime($guess_alert->updated_at));
                $stock = HdPertenStock::where(['period'=>$period, 'curr_time'=>$curr_time])->first();
                if ($stock) {
                    $data['time'] = $curr_time;
                    $data['change'] = $stock->change_status;//1涨/2跌
                    $money = HdPertenGuessLog::where(['period'=>$period, 'user_id'=>$userId])->whereRaw(" date(created_at) = {$curr_time} ")->value('money');
                    $data['money'] = $money ? $money : 0;
                    $guess_alert->alert = 1;
                    $guess_alert->save();
                    $result['alert'] = $data;
                }
            }
            $guessKey = PerBaiService::$guessKeyUser . $activity['id'];
            $result['number'] = Attributes::getNumber($userId, $guessKey);
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
            $return['time'] = date('Y-m-d', strtotime($stock['curr_time']));
            $return['change'] = $stock['change'];
            $return['stock'] = $stock['stock'];
        } else {
            $flag = true;
            $stock = HdPertenStock::where(['period'=>$acvitity['id']])->orderBy('id', 'desc')->first();
            if ($stock && $stock->open_status == 2) {
                $return['time'] = date('Y-m-d', strtotime($stock['curr_time']));
                $return['change'] = $stock['change'];
                $return['stock'] = $stock['stock'];
                $flag = false;
            }
            if ($flag && $h > 1530) {
                $stock = HdPertenStock::where(['curr_time'=>$date, 'period'=>$acvitity['id']])->first();
                if ($stock) {
                    $return['time'] = date('Y-m-d', strtotime($stock['curr_time']));
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
        $guessKey = PerBaiService::$guessKeyUser . $activity['id'];
        $userAttr = Attributes::getItemLock($userId, $guessKey);
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
        if (!$activity) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
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
    public function stockMyRecord() {
        global $userId;
        if (!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $activity = PerBaiService::getActivityInfo();
        if (!$activity) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $period = $activity['id'];
        //$data = Cache::remeber('perten_guess' . $userId, 10, function() use ($userId, $period) {
        $list = HdPertenStock::select(['curr_time', 'stock', 'change'])->where(['period'=>$period])->orderBy('id', 'desc')->get()->toArray();
        foreach ($list as $k=>$v) {
            $userUp = intval(HdPertenGuess::selectRaw("sum(number) total")->where(['period'=>$period, 'user_id'=>$userId, 'type'=>1])->whereRaw(" date(created_at) = '{$v['curr_time']}'")->value('total'));
            $userDown = intval(HdPertenGuess::selectRaw("sum(number) total")->where(['period'=>$period, 'user_id'=>$userId, 'type'=>2])->whereRaw(" date(created_at) = '{$v['curr_time']}'")->value('total'));
            $userMoney = HdPertenGuessLog::where(['period'=>$period, 'user_id'=>$userId])->value('money');
            if($userUp == 0 && $userDown == 0){
                unset($list[$k]);
            }else{
                $list[$k]['up'] = $userUp;
                $list[$k]['down'] = $userDown;
                $list[$k]['money'] = intval( $userMoney);
            }

        }
//            return $list;
//        });
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ];
    }

    protected function getStockNext($date)
    {
        $w = date('w', strtotime($date));
        if ($w == 6 || $w == 0) {
            $date = date('Y-m-d', strtotime('+1 week last monday', strtotime($date)));
            return $this->getStockNext($date);
        }
        if ( in_array($date, ['2019-05-01', '2019-06-07', '2019-09-13', '2019-10-01','2019-10-02','2019-10-03','2019-10-04','2019-10-07']) ) {
            $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
            return $this->getStockNext($date);
        }
        return $date;
    }
}

