<?php

namespace App\Http\JsonRpcs;
use App\Models\GlobalAttribute;
use App\Models\HdAssistance;
use App\Service\ActivityService;
use App\Service\Attributes;
use App\Exceptions\OmgException;
use App\Service\Func;
use DB,Request;

class AssistanceJsonRpc extends JsonRpc
{
    private $key = 'assistance_';
    private $groupTotal = 50;
    /**
     *  生成分享链接
     *
     * @JsonRpcMethod
     */

    public function assistanceShareUrl(){
        global $userId;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $baseUrl = env('APP_URL');
        $shareCode = urlencode(authcode($userId."-".date('Ymd'),'ENCODE',env('APP_KEY')));
        $userInfo = Func::getUserBasicInfo($userId,true);
        $shareUrl = $baseUrl."/active/new_year/luck_draw.html?shareCode=".$shareCode."&inviteCode=".$userInfo['invite_code'];
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $shareUrl
        );
    }
    /**
     *分享助力页面数据
     * @JsonRpcMethod
     */
    public function assistanceInfo(){
        global $userId;
        $res = ['activity_num'=>0,'surplus'=>0,"today_group_open_status"=>false,'award_list'=>[],'my_group_data'=>[],'my_assistance'=>[],'my_group_list'=>[],'my_award_list'=>[]];
        //生成全局限制属性
        $this->_attribute();
        //获取参与人数
        $groupUserCount = HdAssistance::groupBy("group_user_id")->count();
        $userCount = HdAssistance::groupBy("user_id")->count();
        $res['activity_num'] = $groupUserCount + $userCount;
        //获取剩余团数
        $groupCount = GlobalAttribute::select("number")->where('key',$this->key.date("Ymd"))->first();
        $res['surplus'] = isset($groupCount['number']) && $groupCount['number'] > 0 ? $this->groupTotal - $groupCount['number'] : $groupCount['number'];
        //获奖列表
        $receiveList = HdAssistance::select("user_id","award")->where('receive_status',1)->take(10)->orderBy("updated_at","desc")->get()->toArray();
        if(!empty($receiveList)){
            foreach($receiveList as $item){
                if(isset($item['user_id']) && isset($item['award'])){
                    if($item['award'] == 1){
                        $award = "榨汁机";
                    }else{
                        $award = "足浴桶";
                    }
                    $userId = $this->_getUserInfo($item['user_id']);
                    $res['award_list'][] = $userId['mobile']."刚刚助力成功，获得".$award."礼品";
                }
            }
        }
        if ($userId > 0) {//登录获取我的信息
            //判断今天是否已开团
            $isOpen = HdAssistance::where("group_user_id", $userId)->where("day",date("Ymd"))->count();
            $res['today_group_open_status'] = $isOpen > 0 ? false : true;
            //我的团
            $res['my_group_data'] = $this->_groupData($userId);
            //我助力的团
            $assistanceData = HdAssistance::where("user_id", $userId)->orderBy("id", "desc")->first();
            if (isset($assistanceData['group_user_id']) && isset($assistanceData['pid'])) {
                $res['my_assistance'] = $this->_assistanceData($assistanceData['group_user_id'], $assistanceData['pid']);
            }
            //我的团列表
            $myGroupList = HdAssistance::where('group_user_id', $userId)->where("pid", 0)->orderBy("id", "asc")->get()->toArray();
            if (!empty($myGroupList)) {
                foreach ($myGroupList as $item) {
                    if (isset($item['group_ranking'])) {//我的团列表
                        $res['my_group_list'][] = ['ranking' => $item['group_ranking'], "count" => $item['group_num'], "status" => $item['group_num'] >= 3 ? true : false];
                    }
                    if (isset($item['group_ranking'])) {//我的奖品列表
                        $res['my_award_list'][] = ['ranking' => $item['group_ranking'], "award" => $item['award'] == 1 ? "榨汁机" : "足浴桶", "status" => $item['receive_status'] == 1 ? true : false];
                    }
                }
            }
            $myAssistanceList = HdAssistance::where('user_id', $userId)->orderBy("id", "asc")->first();
            if (isset($myAssistanceList['group_ranking'])) {
                $myAssistance = ['ranking' => $myAssistanceList['group_ranking'], "award" => $myAssistanceList['award'] == 1 ? "榨汁机" : "足浴桶", "status" => $myAssistanceList['receive_status'] == 1 ? true : false];
                array_push($res['my_award_list'], $myAssistance);
            }
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res
        );
    }
    /**
     * 开团选择奖品前判断接口
     * @JsonRpcMethod
     */
    public function assistanceCreateBefore(){
        global $userId;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $activityInfo = ActivityService::GetActivityInfoByAlias('assistance_real_name');
        if(empty($activityInfo)){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //生成全局限制属性
        $this->_attribute();
        //锁住每天限制总数属性
        $globalAtt = GlobalAttribute::where('key',$this->key.date("Ymd"))->lockForUpdate()->first();
        if(isset($globalAtt['number']) && $globalAtt['number'] >= $this->groupTotal){//开团超过50
            DB::rollBack();//回滚事物
            throw new OmgException(OmgException::OPENING_MORE_50);
        }
        //判断今天有没有开团
        $groupInfo = HdAssistance::where('group_user_id',$userId)->where("day",date("Ymd"))->first();
        //判断今天开团数量
        if(!empty($groupInfo)){//开团数已达上限
            //判断之前的团有没有未满的
            $notFull = HdAssistance::where('group_user_id',$userId)->where("receive_num","<",3)->count();
            if($notFull > 0){
                //之前有未满团提示
                DB::rollBack();//回滚事物
                throw new OmgException(OmgException::INCOMPLETE_REGIMENT);
            }else{
                //之前已满团提示
                DB::rollBack();//回滚事物
                throw new OmgException(OmgException::FULL_REGIMENT);
            }
        }
        //开团成功返回
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => true
        );
    }
    /**
     * 开团接口
     * @JsonRpcMethod
     */
    public function assistanceCreate($params){
        global $userId;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $awardId = isset($params->award_id) ? $params->award_id : 0;
        if($awardId <= 0){//缺少必要参数
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $activityInfo = ActivityService::GetActivityInfoByAlias('assistance_real_name');
        if(empty($activityInfo)){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //事物开始
        DB::beginTransaction();
        //生成全局限制属性
        $this->_attribute();
        //锁住每天限制总数属性
        $globalAtt = GlobalAttribute::where('key',$this->key.date("Ymd"))->lockForUpdate()->first();
        if(isset($globalAtt['number']) && $globalAtt['number'] >= $this->groupTotal){//开团超过50
            DB::rollBack();//回滚事物
            throw new OmgException(OmgException::OPENING_MORE_50);
        }
        //判断今天有没有开团
        $groupInfo = HdAssistance::where('group_user_id',$userId)->where("day",date("Ymd"))->first();
        //判断今天开团数量
        if(!empty($groupInfo)){//今日已开团
            //今日已开团
            DB::rollBack();//回滚事物
            throw new OmgException(OmgException::TODAY_IS_OPEN);
        }
        //添加开团数据
        $rankingMax = HdAssistance::select(DB::raw('MAX(`group_ranking`) as ranking'))->first();
        $rankingMax = isset($rankingMax['ranking']) && $rankingMax['ranking'] > 0 ? $rankingMax['ranking'] + 1 : 1;
        HdAssistance::create(['group_user_id'=>$userId,'award'=>$awardId,'day'=>date("Ymd"),'group_ranking'=>$rankingMax]);
        //总限制+1
        $globalAtt->increment("number",1);
        $globalAtt->save();
        //提交事物
        DB::commit();
        //开团成功返回
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => true
        );
    }
    /**
     *判断是否满团
     * @JsonRpcMethod
     */
    public function assistanceGroupIsFull($params){
        $groupId = isset($params->group_id) ? $params->group_id : 0;
        if($groupId <= 0){//缺少必要参数
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $activityInfo = ActivityService::GetActivityInfoByAlias('assistance_real_name');
        if(empty($activityInfo)){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //判断团id是否已满
        $groupInfo = HdAssistance::where("id",$groupId)->where("group_num","<",3)->first();
        if(isset($groupInfo['id'])){//未满团
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => false
            );
        }
        return array(//已满团
            'code' => 0,
            'message' => 'success',
            'data' => true
        );
    }
    /**
     *分享助力注册人添加数据
     * @JsonRpcMethod
     */
    public function assistanceAddUser($params){
        global $userId;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $inviteId = isset($params->invite_id) ? $params->invite_id : 0;
        $groupId = isset($params->group_id) ? $params->group_id : 0;
        if($inviteId <= 0 || $groupId <= 0){//缺少必要参数
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $activityInfo = ActivityService::GetActivityInfoByAlias('assistance_real_name');
        if(empty($activityInfo)){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //判断邀请关系和注册时间是否正确
        $userInfo = Func::getUserBasicInfo($userId,false);
        $fromId = isset($userInfo['from_user_id']) ? $userInfo['from_user_id'] : 0;
        $registerTime = isset($userInfo['create_time']) ? $userInfo['create_time'] : '';
        if($inviteId != $fromId || $registerTime < $activityInfo['start_at'] || $registerTime > $activityInfo['end_at']){
            throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
        }
        //判断团id是否已满
        $groupInfo = HdAssistance::where("id",$groupId)->where("group_user_id",$inviteId)->where("group_num","<",3)->first();
        if(!isset($groupInfo['id'])){
            throw new OmgException(OmgException::ONEYUAN_FULL_FAIL);
        }
        //判断是否助力过该团
        $count = HdAssistance::where("group_user_id",$inviteId)->where("user_id",$userId)->count();
        if($count > 0){
            throw new OmgException(OmgException::ALREADY_EXIST);
        }
        //添加到团里，且未实名
        HdAssistance::insertGetId([
            'group_user_id' => $inviteId,
            'group_ranking' => isset($groupInfo['group_ranking']) ? $groupInfo['group_ranking'] : 0,
            'pid' => $groupId,
            'user_id' => $userId,
            'award' => isset($groupInfo['award']) ? $groupInfo['award'] : 0,
            'day' => date("Ymd"),
            'created_at' => date("Y-m-d H:i:s")
        ]);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => true
        );
    }
    /**
     * 领取实物奖励
     * @JsonRpcMethod
     */
    public function assistanceReceive($params){
        global $userId;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $groupUserId = isset($params->group_user_id) ? $params->group_user_id : 0;
        if($groupUserId <= 0){//缺少必要参数
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $activityInfo = ActivityService::GetActivityInfoByAlias('assistance_real_name');
        if(empty($activityInfo)){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //判断团id是否已满
        $groupInfo = HdAssistance::where("group_user_id",$groupUserId)->where('group_num',">=",3)->first();
        if(isset($groupInfo['id'])){//已满团
            $userStatus = HdAssistance::where("group_user_id",$groupUserId)->where("user_id",$userId)->where('status',1)->first();
            if(isset($userStatus['id'])){//领取成功
                //修改领取状态
                HdAssistance::where("group_user_id",$groupUserId)->where("user_id",$userId)->update(['receive_status'=>1,'updated_at'=>date("Y-m-d H:i:s")]);
                return array(
                    'code' => 0,
                    'message' => 'success',
                    'data' => true
                );
            }
            return array(//领取失败
                'code' => 0,
                'message' => 'success',
                'data' => false
            );
        }
        return array(//领取失败
            'code' => 0,
            'message' => 'success',
            'data' => false
        );
    }
    //判断每天总限制是否存在
    private function _attribute(){
        $count = GlobalAttribute::where('key' , $this->key.date("Ymd"))->count();
        if(!$count) {//不存在添加
            GlobalAttribute::insertGetId(['key' => $this->key.date("Ymd"),  'number' => 0]);
        }
        return true;
    }
    //获取我的团数据
    private function _groupData($userId){
        $groupData = HdAssistance::select("id","group_user_id","group_ranking","group_num")->where("group_user_id",$userId)->where("pid",0)->orderBy("pid","desc")->orderBy('id', 'ASC')->get()->toArray();
        $res = [];
        $groupInfo = $this->_getUserInfo($userId);
        if(count($groupData) > 0){
            $i = 1;
            foreach($groupData as $item){
                //团id
                $groupInfo['group_id'] = $item['id'];
                //第几团
                $groupInfo['ranking'] = $item['group_ranking'];
                //团人员
                $groupInfo['group_count'] = 3-$item['group_num'];
                //判断有没有助力成功人
                $userData = HdAssistance::select("pid","user_id")->where("group_user_id",$userId)->where("status",1)->get()->toArray();
                $res[$item['id']][0] = $groupInfo;//第一条团长信息
                if(count($userData) > 0){
                    foreach ($userData as $val){
                        if($item['id'] == $val['pid']){
                            $res[$item['id']][$i] = isset($val['user_id']) && $val['user_id'] > 0 ? $this->_getUserInfo($val['user_id']) : [];//团员信息
                        }
                    }
                    $i++;
                }
            }
            if(count($res) > 0){
                $result = [];
                foreach($res as $val){
                    $result[] = $val;
                }
                return $result;
            }
        }
        return $res;
    }
    //获取我参与的团数据
    private function _assistanceData($userId,$pid){
        $groupData = HdAssistance::select("id","pid","group_user_id","user_id","group_ranking","group_num")->where("pid",$pid)->where('status',1)->orderBy("pid","desc")->orderBy('id', 'ASC')->get()->toArray();
        $res = [];
        $groupInfo = $this->_getUserInfo($userId);
        if(count($groupData) > 0){
            $i = 1;
            foreach($groupData as $item){
                if($item['pid'] > 0){
                    //团id
                    $groupInfo['group_id'] = $item['id'];
                    //第几团
                    $groupInfo['ranking'] = $item['group_ranking'];
                    //团人员
                    $groupInfo['group_count'] = 3-$item['group_num'];
                    $res[$item['pid']][0] = $groupInfo;//第一条为团长信息
                    $res[$item['pid']][$i] = isset($item['user_id']) && $item['user_id'] > 0 ? $this->_getUserInfo($item['user_id']) : [];//团员信息
                    $i++;
                }
            }
            if(count($res) > 0){
                $result = [];
                foreach($res as $val){
                    $result[] = $val;
                }
                return $result;
            }
        }
        return $res;
    }
    //根据用户id获取头像和手机号
    private function _getUserInfo($userId){
        $res = ["photo" =>"","mobile"=>""];
        $info = Func::getUserBasicInfo($userId,false);
        $res['photo'] = isset($info['avatar']) ? $info['avatar'] : "";
        $res['mobile'] = isset($info['username']) && !empty($info['username']) ? substr_replace($info['username'], '****', 3, 6) : "";
        return $res;
    }
}
