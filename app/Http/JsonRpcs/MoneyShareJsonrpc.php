<?php

namespace App\Http\JsonRpcs;

use App\Models\Activity;
use App\Exceptions\OmgException;
use App\Service\MoneyShareBasic;
use App\Models\MoneyShare;
use App\Models\MoneyShareInfo;
use App\Models\MoneyShareRelation;
use App\Service\Func;
use Lib\JsonRpcClient;
use Illuminate\Contracts\Encryption\DecryptException;
use DB, Config, Request;
use App\Models\WechatUser;
class MoneyShareJsonRpc extends JsonRpc {

    /**
     *  发送体验金
     *
     * @JsonRpcMethod
     */
    public function moneyShareSendAward($params) {
        global $userId;
        $result = ['isLogin'=>1, 'award' => 0, 'isGot' => false, 'mall' =>[] , 'recentList' => [], 'topList' => []];
        if(empty($userId)){
            $result['isLogin'] = 0;
        }
        $identify = $params->identify;
        if(empty($identify)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }

        // 商品是否存在
        $date = date("Y-m-d H:i:s");
        DB::beginTransaction();
        $mallInfo = MoneyShare::where(['identify' => $identify, 'status' => 1])
            ->where("start_time","<=",$date)
            ->where("end_time",">=",$date)
            ->lockForUpdate()->first();
        if(!$mallInfo){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $recentList = MoneyShareInfo::where('main_id', $mallInfo['id'])->orderBy('id', 'desc')->take(5)->get();
        $topList = MoneyShareInfo::where('main_id', $mallInfo['id'])->orderBy('money', 'desc')->orderBy('created_at', 'asc')->take(5)->get();
        $result['recentList'] = self::_formatData($recentList);
        $result['topList'] = self::_formatData($topList);

        $result['mall'] = $mallInfo;
        // 计算剩余金额和剩余数量
        $remain = $mallInfo->total_money - $mallInfo->use_money;
        $remain = $remain > 0 ? $remain : 0;
        $remainNum = $mallInfo->total_num - $mallInfo->receive_num;
        $remainNum = $remainNum > 0 ? $remainNum : 0;

        //用户领取过
        if($result['isLogin']){
            $join = MoneyShareInfo::where(['user_id' => $userId, 'main_id' => $mallInfo->id])->first();
            if($join){
                $result['isGot'] = 1;
                $result['award'] = $join['money'];
                return array(
                    'code' => 0,
                    'message' => 'success',
                    'data' => $result
                );
            }
            //奖品已抢光
            if($remainNum == 0){
                $result['isGot'] = 2;
            }
        }


        // 发体验金
        if($result['isLogin'] && !$result['isGot']) {
            $money = MoneyShareBasic::getRandomMoney($remain,$remainNum,$mallInfo->min,$mallInfo->max);
            $mallInfo->increment('use_money', $money);
            $mallInfo->increment('receive_num', 1);

            //发送体验金
            $expRes = MoneyShareBasic::sendAward($userId, $mallInfo['award_type'], $mallInfo['award_id'], $money, $mallInfo['id']);
            MoneyShareInfo::create([
                'user_id' => $userId,
                'main_id' => $expRes['award']['main_id'],
                'uuid' => $expRes['award']['uuid'],
                'money' => $money,
                'source_id' => $expRes['award']['source_id'],
                'award_type' => $mallInfo['award_type'],
                'award_id' => $mallInfo['award_id'],
                'remark' => json_encode($expRes['remark'], JSON_UNESCAPED_UNICODE),
                'mail_status' => $expRes['mail_status'],
                'message_status' => $expRes['message_status'],
                'status' => $expRes['status'],
            ]);
            if(!$expRes['status']) {
                throw new OmgException(OmgException::API_FAILED);
            }
            $result['award'] = $money;
        }
        DB::commit();


        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $result
        );
    }
    //将列表的数据整理出手机号
    public static function _formatData($data){
        if(empty($data)){
            return $data;
        }
        foreach ($data as &$item){
            if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
        return $data;
    }
    /**
     *  根据交易记录id添加红包分享数据
     *
     * @JsonRpcMethod
     */
    public function moneyShareByRecordID($params) {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $recordId = intval($params->recordId);
        if(empty($recordId)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }

        //根据别名查询该活动是否开启
        $where['alias_name'] = "money_share_for_user";
        $where['enable'] = 1;
        $isExist = Activity::where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->where($where)->count();

        if(!$isExist){
            //不存在返回值
            $return = array();
            $return['enable'] = 0;
            $return['share'] = array();
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $return
            );
        }

        //根据recordId获取该用户投资金额
        $url = env("MARK_HTTP_URL");
        $client = new JsonRpcClient($url);
        $info = $client->GetBuydetailMsg(array("buydetailId"=>$recordId));

        //调用韩兆兴接口
        $money = isset($info['result']['total_amount']) && !empty($info['result']['total_amount']) ? intval($info['result']['total_amount']) : 0;
        if(empty($money)){
            throw new OmgException(OmgException::DATA_ERROR);
        }

        //判断红包分享数据是否添加过
//        $where = array();
//        $where['record_id'] = $recordId;
//        $where['user_id'] = $userId;
//        $where['status'] = 1;
//        $res = MoneyShare::where('created_at', 'like', date("Y-m-d").'%')->where($where)->count();
        if($money < 500) {
            //不存在返回值
            $return = array();
            $return['enable'] = 0;
            $return['share'] = array();
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $return
            );
        }else{
            //红包规则计算
            if($money < 5000){
                $money = 5000;
            }
            //投资金额大于于2万配置
            $userRedNum = 10;
            $userRedMin = 1000;
            //投资金额小于2万配置
            if($money < 20000){
                $userRedNum = 5;
                $userRedMin = 500;
            }
            $shareMoney = intval($money*(mt_rand(60,100)/100));
            //获取微信昵称
            $nickName = WechatUser::where("uid",$userId)->select('nick_name')->first();
            $nickName = isset($nickName['nick_name']) && !empty($nickName['nick_name']) ? $nickName['nick_name'] : "";
            //添加到红包分享表
            $param['user_id'] = $userId;
            $param['user_name'] = $nickName;
            $param['recordId'] = $recordId;
            $param['money'] = $shareMoney;
            $param['total_num'] = $userRedNum;
            $param['min'] = $userRedMin;
            $res = $this->addMoneyShare($param);

            if(!$res){
                throw new OmgException(OmgException::DATABASE_ERROR);
            }
            if(isset($res['id']) && empty($res['id'])){
                throw new OmgException(OmgException::DATABASE_ERROR);
            }
            $result = $res['result'];
        }

        //获取用户手机号
        $phone = Func::getUserPhone($userId,true);

        //返回值
        $return = array();
        $return['enable'] = 1;
        $return['share']['uri'] = env("APP_URL")."/active/red_packet/red_packet.html?k=".$result['identify'];
        $return['share']['title'] = Config::get('moneyshare.user_red_title');
        $return['share']['content'] = Config::get('moneyshare.user_red_content');
        $return['share']['photo_url'] = Config::get('moneyshare.user_red_photo_url');
        $return['share']['total_money'] = $result['total_money'];
        $return['share']['total_num'] = $result['total_num'];
        $return['share']['nick_name'] = $result['user_name'];
        $return['share']['phone'] = !empty($phone) ? substr_replace($phone, '*****', 3, 5) : "";
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $return
        );
    }

    /**
     * 添加到红包分享表中
     * @param $param
     * @return bool
     */
    public function addMoneyShare($param){
        if($param['user_id'] <= 0 || $param['money'] <= 0 || $param['recordId'] <= 0){
            return false;
        }
        //祝福语
        $data['blessing'] = "分享给好友哦";
        //用户ID
        $data['user_id'] = $param['user_id'];
        //用户姓名
        $data['user_name'] = $param['user_name'];
        //奖品类型
        $data['award_type'] = 3;
        //商品id
        $data['award_id'] = 1;
        //总金额
        $data['total_money'] = $param['money'];
        //总数量
        $data['total_num'] = $param['total_num'];
        //最小值
        $data['min'] = $param['min'];
        //最大值
        $data['max'] = 0;
        //开始时间
        $data['start_time'] = date("Y-m-d H:i:s");
        //结束时间
        $data['end_time'] = date("Y-m-d H:i:s",strtotime("+7 days"));
        //红包标示
        $data['identify'] = "record_".$param['recordId']."_".Func::randomStr(15);
        //交易id
        $data['record_id'] = $param['recordId'];
        //状态为上线
        $data['status'] = 1;
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        //修改时间
        $data['updated_at'] = date("Y-m-d H:i:s");
        $id = MoneyShare::insertGetId($data);
        return array('id'=>$id,'result'=>$data);
    }


    /**
     * [投资红包]投资红包记录邀请关系
     *
     * @JsonRpcMethod
     */
    public function addInviteRedPackRelation($params) {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if(!isset($params->identify)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $insert = [
            'user_id' =>  $userId,
            'invite_user_id' => 0,
            'tag' => 'invite',
            'identify' => $params->identify,
            'ip' => Request::getClientIp(),
        ];
        $res = Func::getUserBasicInfo($userId);
        $insert['invite_user_id'] = isset($res['from_user_id']) ? intval($res['from_user_id']) : 0;

        $item = MoneyShareRelation::where([
            'user_id' => $userId,
            'invite_user_id' => $insert['invite_user_id'],
        ])->first();

        if(!$insert['invite_user_id']) {
            throw new OmgException(OmgException::INVITE_USER_NOT_EXIST);
        }
        //已有数据
        if($item) {
            throw new OmgException(OmgException::ALREADY_EXIST);
        }
        MoneyShareRelation::create($insert);

        return array(
            'code' => 0,
            'message' => 'success',
        );
    }

    /**
     * [投资红包]投资红包查询邀请关系
     *
     * @JsonRpcMethod
     */
    public function getInviteRedPackRelationList($params) {
        if(!isset($params->identify)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }

        $res = MoneyShareRelation::select('user_id', 'invite_user_id')->where([
            'tag' => 'invite',
            'identify' => $params->identify
        ])->orderBy('id', 'desc')->take(300)->get();

        return array(
            'code' => 0,
            'message' => 'success',
            'result' => $res
        );
    }

    /**
     * [分享红包]分享红包记录邀请关系
     *
     * @JsonRpcMethod
     */
    public function addShareRedPackRelation($params) {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if(!isset($params->identify)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $insert = [
            'user_id' =>  $userId,
            'invite_user_id' => 0,
            'tag' => 'share',
            'identify' => $params->identify,
            'ip' => Request::getClientIp(),
        ];
        $res = Func::getUserBasicInfo($userId);
        $insert['invite_user_id'] = isset($res['from_user_id']) ? intval($res['from_user_id']) : 0;
        $item = MoneyShareRelation::where([
            'user_id' => $userId,
            'invite_user_id' => $insert['invite_user_id'],
        ])->first();

        if(!$insert['invite_user_id']) {
            throw new OmgException(OmgException::INVITE_USER_NOT_EXIST);
        }
        //已有数据，或用户没有邀请人
        if($item) {
            throw new OmgException(OmgException::ALREADY_EXIST);
        }
        // 创建记录
        MoneyShareRelation::create($insert);
        // 给邀请人发奖
        SendAward::ActiveSendAward($insert['invite_user_id'], 'money_share_invite_award');

        return array(
            'code' => 0,
            'message' => 'success',
        );
    }

    /**
     * [分享红包]分享红包查询邀请关系
     *
     * @JsonRpcMethod
     */
    public function getShareRedPackRelationList() {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $res = MoneyShareRelation::select('user_id', 'invite_user_id')->where([
            'tag' => 'share',
            'invite_user_id' => $userId
        ])->orderBy('id', 'desc')->take(300)->get();

        return array(
            'code' => 0,
            'message' => 'success',
            'result' => $res
        );
    }

    /**
     * [分享红包] 分享后获得1000元体验金
     *
     * @JsonRpcMethod
     */
    public function getShareRedPackAward() {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $status = SendAward::ActiveSendAward($userId, 'money_share_share_award');
        if(isset($status['msg'])){
            if($status['msg'] == "频次验证不通过"){
                throw new OmgException(OmgException::MALL_IS_HAS);
            }
            if($status['msg'] == "活动不存在！"){
                throw new OmgException(OmgException::AWARD_NOT_EXIST);
            }
            if($status['msg'] == "发奖失败！"){
                throw new OmgException(OmgException::SEND_ERROR);
            }
        }
        $awardName = isset($status[0]['award_name']) ? $status[0]['award_name'] : '';
        return array(
            'code' => 0,
            'message' => 'success',
            'data'=>array("award_name"=>$awardName)
        );
    }
}

