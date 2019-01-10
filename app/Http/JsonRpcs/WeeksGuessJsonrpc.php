<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\HdWeeksGuess;
use App\Models\HdWeeksGuessConfig;
use App\Service\ActivityService;
use App\Service\Attributes;
use App\Service\Func;
use App\Service\GlobalAttributes;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Config, Cache,DB;

class WeeksGuessJsonrpc extends JsonRpc
{
    use DispatchesJobs;

    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function weeksGuessInfo() {
        global $userId;
        $result = [
                'login' => false,
                'available' => 0,//1开始，0 结束
                'start_time' => '',//开始时间
                'end_time' => '',//结束时间
                'guess_status' => 0,//0未开始1竞猜中2竞猜结束
                'guess_overtime' => 0,//竞猜倒计时
                'number' => 0,//竞猜机会
                'join' => 0,//参与人数
                'special' => '',//专场名称
                'race_time' => '',
                'home_team' => '',
                'guest_team' => '',
                'home_img' => '',
                'guest_img' => '',
                'home_score' => 0,
                'guest_score' => 0,
                'recent' => '',
                'activity_rule' => '',
                'home_win' => 0,//主胜
                'home_eq' => 0,//主平
                'home_lose' => 0,//主负
                'rate_win' => 0,//主胜 支持数
                'rate_eq' => 0,//主平 支持数
                'rate_lose' => 0,//主负 支持数
                ];
        // 用户是否登录
        if($userId) {
            $result['login'] = true;
        }
        $config = Config::get('weeksguess');
        $aliasName = $config['alias_name'];
        // 活动是否存在
        if (ActivityService::isExistByAlias($aliasName)) {
            $result['available'] = 1;
        }
        $activity = ActivityService::GetActivityedInfoByAlias($aliasName);
        $result['start_time'] = $activity->start_at;
        $result['end_time'] = $activity->end_at;

        //竞猜时间
        $weeksConfig = HdWeeksGuessConfig::where('status', 1)->first();
        if ($weeksConfig) {
            $guess_start = strtotime($weeksConfig->start_time);
            $guess_end = strtotime($weeksConfig->end_time);
            $time = time();
            if ($time >= $guess_start) {
                $result['guess_status'] = 1;
            }
            if ($time > $guess_end) {
                $result['guess_status'] = 2;
            }
            $difftime = $guess_end - strtotime('now');
            $result['guess_overtime'] = $difftime > 0 ? $difftime : 0;
            //奖池总额
            $result['money'] = number_format($weeksConfig->money);
            $result['special'] = $weeksConfig->special;
            $result['race_time'] = $weeksConfig->race_time;
            $result['home_team'] = $weeksConfig->home_team;
            $result['guest_team'] = $weeksConfig->guest_team;
            $result['home_img'] = $weeksConfig->home_img;
            $result['guest_img'] = $weeksConfig->guest_img;
            $result['home_score'] = $weeksConfig->home_score;
            $result['guest_score'] = $weeksConfig->guest_score;
            $result['recent'] = $weeksConfig->recent;
            $result['activity_rule'] = $weeksConfig->activity_rule;
            //支持率
            $guess_count = HdWeeksGuess::select('type', DB::raw('SUM(number) as total'))->where(['period'=>$weeksConfig->id])->groupBy('type')->get()->toArray();
            if ($guess_count) {
                foreach ($guess_count as $v) {
                    if ($v['type'] == 1) {
                        $result['rate_win'] = $v['total'];
                    } else if ($v['type'] == 2) {
                        $result['rate_eq'] = $v['total'];
                    } else if ($v['type'] == 3) {
                        $result['rate_lose'] = $v['total'];
                    }
                }
            }

        }
        if ($result['login'] && $result['available']) {
            $result['number'] = Attributes::getNumber($userId, $config['drew_user_key']);
            //
            $user_guess = HdWeeksGuess::select('type', DB::raw('SUM(number) as total'))->where(['user_id'=>$userId, 'period'=>$weeksConfig->id])->groupBy('type')->get()->toArray();
            if ($user_guess) {
                foreach ($user_guess as $v) {
                    if ($v['type'] == 1) {
                        $result['home_win'] = $v['total'];
                    } else if ($v['type'] == 2) {
                        $result['home_eq'] = $v['total'];
                    } else if ($v['type'] == 3) {
                        $result['home_lose'] = $v['total'];
                    }
                }
            }
        }

        $result['join'] = HdWeeksGuess::select(DB::RAW('count(distinct user_id) c'))->where('period', $weeksConfig->id)->value('c');
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }


    /**
     * 竞猜
     *
     * @JsonRpcMethod
     */
    public function weeksGuessDraw($params)
    {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('weeksguess');
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $type = isset($params->type) ? intval($params->type) : 0;
        $number = isset($params->num) ? intval($params->num) : 0;
        if( $type <= 0 || $type > 3 || $number <= 0){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $weeksConfig = HdWeeksGuessConfig::where('status', 1)->first();
        if (!$weeksConfig) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        DB::beginTransaction();
        $userAttr = Attributes::getItemLock($userId, $config['drew_user_key']);
        $user_num = isset($userAttr->number) ? $userAttr->number : 0;
        if ( $number > $user_num) {
            DB::rollBack();
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        $insert = HdWeeksGuess::create([
           'user_id'=>$userId,
           'period'=>$weeksConfig->id,
           'type'=>$type,
           'number'=>$number,
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
    public function weeksGuessList() {
        $data = HdWeeksGuess::select(['user_id', 'type', 'number', 'created_at'])->orderBy('id', 'desc')->limit(30)->get()->toArray();
        if ($data) {
            foreach ($data as $k=>$v) {
                $data[$k]['phone'] = Func::getUserPhone($v['user_id']);
            }
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
        $data = HdWeeksGuessConfig::select(['id','race_time', 'home_team', 'guest_team', 'result', 'money'])->where(['status'=>1, 'draw_status'=>1])->first();
        if ($data) {
            $total = HdWeeksGuess::where(['period'=>$data->id, 'type'=>$data->result])->sum('number');
            $data['prize'] = bcdiv($data->money, $total, 2);
            unset($data['id']);
            unset($data['money']);
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 往日赛况
     *
     * @JsonRpcMethod
     */
    public function weeksGuessHistoryRecord() {
        $data = HdWeeksGuessConfig::select(['id','race_time', 'home_team', 'guest_team', 'result', 'money'])->where(['status'=>0, 'draw_status'=>1])->get()->toArray();
        if ($data) {
            foreach ($data as $k=>$v) {
                $total = HdWeeksGuess::where(['period'=>$v['id'], 'type'=>$v['result']])->sum('number');
                $data[$k]['prize'] = bcdiv($v['money'], $total, 2);
                unset($data[$k]['id']);
                unset($data[$k]['money']);
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }
}

