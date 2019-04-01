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
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $res = ['group_data'=>[],'assistance'=>[]];
        //生成全局限制属性
        $this->_attribute();
        //我的团
        $res['group_data'] = $this->_groupData($userId);
        //我助力的团
        $assistanceData = HdAssistance::where("user_id",$userId)->orderBy("id","desc")->first();
        if(isset($assistanceData['group_user_id']) && isset($assistanceData['pid'])){
            $res['assistance'] = $this->_assistanceData($assistanceData['group_user_id'],$assistanceData['pid']);
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res
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
        //判断今天有没有开团
        $groupInfo = HdAssistance::where('group_user_id',$userId)->where("day",date("Ymd"))->first();
        //判断今天开团数量
        if(!empty($groupInfo) || (isset($globalAtt['number']) && $globalAtt['number'] >= 50)){//开团数已达上限
            DB::rollBack();//回滚事物
            throw new OmgException(OmgException::EXCEED_NUM_FAIL);
        }
        //添加开团数据
        HdAssistance::create(['group_user_id'=>$userId,'award'=>$awardId,'day'=>date("Ymd")]);
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
        $groupInfo = HdAssistance::where("id",$groupId)->where("group_num","<",3)->first();
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
            'pid' => $groupId,
            'user_id' => $userId,
            'award' => isset($groupInfo['award']) ? $groupInfo['award'] : 0,
            'day' => date("Ymd")
        ]);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => true
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
        $groupData = HdAssistance::select("pid","user_id")->where("group_user_id",$userId)->where('status',1)->orderBy("pid","desc")->orderBy('id', 'ASC')->get()->toArray();
        $res = [];
        $groupInfo = $this->_getUserInfo($userId);
        if(count($groupData) > 0){
            $i = 1;
            foreach($groupData as $item){
                if($item['pid'] > 0){
                    $res[$item['pid']][0] = $groupInfo;
                    $res[$item['pid']][$i] = isset($item['user_id']) && $item['user_id'] > 0 ? $this->_getUserInfo($item['user_id']) : "--";
                    $i++;
                }
            }
        }
        return $res;
    }
    //获取我参与的团数据
    private function _assistanceData($userId,$pid){
        $groupData = HdAssistance::select("pid","user_id")->where("pid",$pid)->where('status',1)->orderBy("pid","desc")->orderBy('id', 'ASC')->get()->toArray();
        $res = [];
        $groupInfo = $this->_getUserInfo($userId);
        if(count($groupData) > 0){
            $i = 1;
            foreach($groupData as $item){
                if($item['pid'] > 0){
                    $res[$item['pid']][0] = $groupInfo;
                    $res[$item['pid']][$i] = isset($item['user_id']) && $item['user_id'] > 0 ? $this->_getUserInfo($item['user_id']) : "--";
                    $i++;
                }
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
