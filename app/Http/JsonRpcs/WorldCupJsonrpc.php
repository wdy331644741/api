<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\HdWorldCupConfig;
use App\Models\HdWorldCupExtra;
use App\Models\HdWorldCupSupport;
use App\Models\HdWorldCup;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use App\Service\WorldCupService;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Lib\JsonRpcClient;

use Config, Cache,DB;

class WorldCupJsonrpc extends JsonRpc
{
    use DispatchesJobs;

    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function worldCupInfo() {
        global $userId;
        $result = [
                'login' => false,
                'available' => 0,//1开始，0 结束
                'num' => 0, //剩余次数
                'total_ball' => 0,//您所支持球队总进球数+额外进球数
                'invitecode' => '',
                'start' => '',
                'end' => '',
                'rank_list' => [],//本周好友助攻排行榜
                ];
        // 用户是否登录
        if($userId) {
            $result['login'] = true;
            $result['invitecode'] = base64_encode($userId);
        }
        $config = Config::get('worldcup');
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $result['available'] = 1;
        }
        if ($result['available'] && $result['login']) {
            $result['num'] = Attributes::getNumberByDay($userId, $config['drew_user_key']);
            $result['total_ball'] = WorldCupService::getTotalBallCounts($userId);
            $result['rank_list'] = self::getRankList($config);
        }
        if ($dates = self::getCurrDate($config['alias_name'])) {
            $result['start'] = $dates['start'];
            $result['end'] = $dates['end'];
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 我的额外进球数
     *
     * @JsonRpcMethod
     */
    public function worldCupExtraList() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $result = [];
        $config = Config::get('worldcup');
        $extra_ball_num = WorldCupService::getExtraBallCounts($userId);
        $data = self::getExtraBallList($userId);
        $result['extra_ball'] = $extra_ball_num;
        $result['data'] = $data;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 球队进球数排行
     *
     * @JsonRpcMethod
     */
    public function worldCupBallRank() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('world_cup');
//        $result['num'] = intval(Attributes::getNumberByDay($userId, $config['alias_name']));
//        $result['total_ball'] = self::getTotalBallCounts($userId, $config['extra_ball_key']);
        $result['data'] = self::getBallRankList($userId);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 球队支持
     *
     * @JsonRpcMethod
     */
    public function worldCupDraw($params)
    {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('worldcup');
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $id = isset($params->id) ? intval($params->id) : 0;
        if(!$id){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        if (!HdWorldCupConfig::find($id)) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $default = 1;
        DB::beginTransaction();
        $user_num = $this->getUserNum($userId,$config['drew_user_key']);
        if ( $default > $user_num) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        //剩余次数
        $sy_number = $this->reduceUserNum($userId,$config,$default);
        WorldCupService::addBallSupport($userId, $id, $default);
        DB::commit();
        $ret['number'] = $sy_number;
        $ret['total_number'] = WorldCupService::getTotalBallCounts($userId);;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $ret,
        ];
    }

    /**
     * 历史榜首
     *
     * @JsonRpcMethod
     */
    public function worldCupHistoryList() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $data = self::getHistoryRankTop();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 我的助攻数 每周 总助攻数
     *
     * @JsonRpcMethod
     */
    public function worldCupHelpList() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $data = self::getMyHelpList($userId);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 我的瓜分现金
     *
     * @JsonRpcMethod
     */
    public function worldCupAwardShow()
    {
        global $userId;
        $result = [
            'status'=>0,
            'amount'=>0
        ];
        // 用户是否登录
        if(!$userId) {
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $result,
            ];
        }
        $config = Config::get('worldcup');
        $activityTime = ActivityService::GetActivityedInfoByAlias($config['alias_name']);
        if($activityTime && strtotime($activityTime->end_at) > time() ){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $result,
            ];
        }

        $amount = self::getCashByUserId($userId);
        $result['amount'] = $amount;
        $result['status'] = 1;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];

    }
    /**
     * 邀请页面返回手机号
     *
     * @JsonRpcMethod
     */
    public function worldCupInvite($params)
    {
        $invitecode = isset($params->invitecode) ? $params->invitecode : '';
        if ($invitecode) {
            $invite_userid = base64_decode($invitecode);
            $user_phone = Func::getUserPhone($invite_userid);
        }
        $result['phone'] = isset($user_phone) ? $user_phone : '';
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }


    //用户支持次数
    private function getUserNum($userId, $key) {
        $user_attr = Attributes::getItemLock($userId, $key);
        if ($user_attr && $user_attr->number > 0) {
            return $user_attr->number;
        }
        return 0;
    }

    //减少支持次数,
    // 增加总支持次数
    private function reduceUserNum($userId,$config,$num){
        //将总共的抽奖次数累加
        Attributes::increment($userId,$config['drew_total_key'],$num);
        //减少用户抽奖次数
        $number = Attributes::decrement($userId,$config['drew_user_key'],$num);
        return $number;
    }

    //额外进球列表  邀请好友手机号， 时间， 需用户组返回用户的邀请好友列表
    protected  static function getExtraBallList($userId) {

        $data = HdWorldCupExtra::select('f_userid', 'number', 'type', 'created_at')->where(['user_id'=>$userId, 'type'=>2])->get()->toArray();
        if (empty($data)) {
            return [];
        }
        foreach ($data as &$val) {
            $phone = Func::getUserPhone($val['f_userid']);
            $val['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
        }
        return $data;
    }

    protected static function getCurrDate($key) {
        $date_group = WorldCupService::getDateList($key);
        $date = time();
        foreach ($date_group as $val) {
            if ($date >=strtotime($val['start']) && $date <=strtotime($val['end'])) {
                return $val;
            }
        }
        return [];
    }
    //本周好友助攻排行榜; 显示三条
    protected static function getRankList($config) {
        $curr_week = self::getCurrDate($config['alias_name']);
        if (!$curr_week) {
            return [];
        }
        $data = HdWorldCupExtra::selectRaw('user_id, SUM(number) AS total_num')
                    ->where(['type'=>2])
                    ->whereBetween('created_at', [$curr_week['start'], $curr_week['end']])
                    ->groupBy('user_id')
                    ->orderBy('total_num', 'desc')
                    ->get()->toArray();
        if (empty($data)) {
            return [];
        }
        foreach ($data as &$val) {
            $phone = Func::getUserPhone($val['user_id']);
            $val['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
        }
        return $data;
    }

    //历史助攻榜首列表 （当前是第三周,  显示第一周的第一名和第二周的第一名）
    protected static function getHistoryRankTop() {
        $config = Config::get('worldcup');
        $date_group = WorldCupService::getDateList($config['alias_name']);
        $date = time();
        $history_list = [];
        foreach ($date_group as $val) {
            if ($date >=strtotime($val['end'])) {
                $data = HdWorldCupExtra::selectRaw('user_id, SUM(number) AS total_num')
                    ->where(['type'=>2])
                    ->whereBetween('created_at', [$val['start'], $val['end']])
                    ->groupBy('user_id')
                    ->orderBy('total_num', 'desc')
                    ->first()->toArray();
                if ($data) {
                    $history_list[] = array_merge($data, $val);
                }
            }
        }
        if (empty($history_list)) {
            return [];
        }
        foreach ($history_list as &$val) {
            $phone = Func::getUserPhone($val['user_id']);
            $val['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
        }
        return $history_list;
    }
    //球队进球数排行
    protected static function getBallRankList($userId) {
        $world_cup_config = HdWorldCupConfig::select('id', 'team', 'number')->orderBy('number', 'desc')->get()->toArray();
        $world_cup_support = HdWorldCupSupport::select('world_cup_config_id', 'number')->where(['user_id'=>$userId])->get()->toArray();
        foreach($world_cup_config as &$val) {
            $val['support'] = 0;
            foreach( $world_cup_support as $v) {
                if ($val['id'] == $v['world_cup_config_id']) {
                    $val['support'] = $v['number'];
                }
            }
        }
        return $world_cup_config;
    }

    public static function getMyHelpList($userId) {
        $config = Config::get('worldcup');
        $date_group = WorldCupService::getDateList($config['alias_name']);
        $date = time();
        $list = [];
        foreach ($date_group as $val) {
            if ($date >=strtotime($val['end'])) {
                $data = HdWorldCupExtra::selectRaw('SUM(number) AS number')
                    ->where(['type'=>2])
                    ->where(['user_id'=>$userId])
                    ->whereBetween('created_at', [$val['start'], $val['end']])
                    ->groupBy('user_id')
                    ->first()->toArray();
                if ($data) {
                    $list[] = array_merge($data, $val);
                }
            }
        }
        if (empty($list)) {
            return [];
        }
        return $list;
    }

    public static function getCashByUserId($userId)
    {
        $where['user_id'] = $userId;
        $where['status'] = 1;
        $worldcup = HdWorldCup::select(['amount'])->where($where)->first();
        if ($worldcup) {
            return $worldcup->amount;
        }
        return 0;
    }

//    protected  static function getAllBallCounts() {

//        select `hd_world_cup_config`.`id`, `hd_world_cup_config`.`team`, `hd_world_cup_config`.`number`, sum(`s`.`number`) count from `hd_world_cup_config`
//	left join `hd_world_cup_support` as `s`
//	on `hd_world_cup_config`.`id` = `s`.`world_cup_config_id`
//	where
//		`hd_world_cup_config`.`deleted_at` is null
//	GROUP BY id
//	order by `hd_world_cup_config`.`number` desc
//        $world_cup_config = HdWorldCupConfig::select(['hd_world_cup_config.id','hd_world_cup_config.team','hd_world_cup_config.number','s.user_id', 's.number AS count'])
//            ->leftJoin('hd_world_cup_support AS s', 'hd_world_cup_config.id','=','s.world_cup_config_id')
//            ->orderBy('hd_world_cup_config.number', 'desc')
//            ->get()->toArray();
//        $sum = 0;
//        foreach ($world_cup_config as $val) {
//            $sum += (intval($val['number']) * intval($val['count']));
//        }
//        return $sum;
//    }
}

