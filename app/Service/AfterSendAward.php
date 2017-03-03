<?php
namespace App\Service;
use App\Service\Attributes;
use App\Service\SendAward;
use App\Models\UserAttribute;

class AfterSendAward
{

    /**
     * 按照充值金额送体验金
     * @param $activityInfo
     * @param $triggerData
     * @return mixed
     */
    static function afterSendAward($activityInfo, $triggerData)
    {
        $return = array();
        $Attributes = new Attributes();

        if (!$activityInfo['alias_name']) {
            return $return;
        }

        switch ($activityInfo['alias_name']) {
            /**进阶活动*****开始****/
            //注册
            case "advanced_register":
                $Attributes->advanced($triggerData['user_id'],'advanced','advanced_register:1');
                break;
            //实名
            case "advanced_real_name":
                $Attributes->advanced($triggerData['user_id'],'advanced','advanced_real_name:1');
                break;
            //投资
            case "advanced_target_term":
                //获取刘奇接口取得标期限
//                $period = array(1,3,6,12);
//                foreach($period as $item){
//                    SendAward::ActiveSendAward($triggerData['user_id'],"advanced_target_term_".$item);
//                }
                break;
            //投资1月标(主动)
            case "advanced_target_term_1":
                $Attributes->advanced($triggerData['user_id'],'advanced','advanced_target_term_1:1');
                break;
            //投资3月标(主动)
            case "advanced_target_term_3":
                $Attributes->advanced($triggerData['user_id'],'advanced','advanced_target_term_3:1');
                break;
            //投资6月标(主动)
            case "advanced_target_term_6":
                $Attributes->advanced($triggerData['user_id'],'advanced','advanced_target_term_6:1');
                break;
            //投资12月标(主动)
            case "advanced_target_term_12":
                $Attributes->advanced($triggerData['user_id'],'advanced','advanced_target_term_12:1');
                break;
            //连续签到3天
            case "advanced_signin_3":
                $Attributes->advanced($triggerData['user_id'],'advanced','advanced_signin_3:1');
                break;
            //邀请好友首投累计
            case "advanced_invite":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id'])) {
                    $count = $Attributes->increment($triggerData['from_user_id'] ,"advanced_invite_first",1);
                    if($count >= 3){
                        SendAward::ActiveSendAward($triggerData['from_user_id'],"advanced_invite_3");
                    }
                }
                break;
            //邀请好友首投3人（主动）
            case "advanced_invite_3":
                $Attributes->advanced($triggerData['user_id'],'advanced','advanced_invite_3:1');
                break;
            //微信绑定触发
            case "advanced_wechat":

                break;
            //微信首次绑定（主动）
            case "advanced_wechat_first":
                $Attributes->advanced($triggerData['user_id'],'advanced','advanced_wechat_first:1');
                break;
            /**进阶活动*****结束****/
        }
        return $return;
    }
}