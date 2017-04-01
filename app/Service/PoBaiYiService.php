<?php
namespace App\Service;

use App\Models\PoBaiYi;
use App\Service\Func;
use App\Service\SendMessage;
use App\Exceptions\OmgException;

class PoBaiYiService
{

    /**
     * 根据投资信息送现金
     *
     * @param $num
     * @return array
     */
    static function addMoneyByInvestment($investment) {
        $amount = isset($investment['Investment_amount']) ? intval($investment['Investment_amount']) : 0;
        $amountMultiple = intval($amount/10000);
        $userId = $investment['user_id'];
        if($amountMultiple == 0) {
            return;
        }
        if($investment['scatter_type'] == 2) {
            if($investment['period'] < 2) {
                $periodMultiple = 0;
            }elseif($investment['period']<4) {
                $periodMultiple = 1;
            }elseif($investment['period']< 7) {
                $periodMultiple = 2;
            }elseif($investment['period']< 13){
                $periodMultiple = 3;
            }elseif($investment['period']> 12) {
                $periodMultiple = 4;
            }else{
                $periodMultiple = 0;
            }
        }else{
            $periodMultiple = 0;
        }

        $min = $periodMultiple*5;
        $max = $min + 5;
        $min = $min == 0 ? 1 : $min;

        $intMoney = rand($amountMultiple*$min, $amountMultiple*$max-1);
        $dotMoney = bcdiv(rand(1,99), 100, 2);
        $money = bcadd($intMoney, $dotMoney, 2);
        PoBaiYiService::addMoney($userId, $money);
    }
    /**
     * 根据倍数送现金
     *
     * @param $num
     * @return array
     */
    static function addMoney($userId, $money, $remark = []) {
        $uuid = SendAward::create_guid();

        // 创建记录
        $awardName = $money . '元现金';
        $result['amount'] = $money;
        $res = PoBaiYi::create([
            'user_id' => $userId,
            'award_name' => $awardName,
            'uuid' => $uuid,
            'amount' => $money,
            'type' => 7,
            'status' => 0,
            'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
        ]);

        $addMoneyRes = Func::incrementAvailable($userId, $res->id, $uuid, $money, 'billion_carnival');


        $remark['addMoneyRes'] = $addMoneyRes;
        // 成功
        if(isset($addMoneyRes['result'])) {
            $res->update(['status' => 1, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
            PoBaiYiService::sendMsg($userId);
        }

        // 失败
        if(!isset($addMoneyRes['result'])) {
            $res->update(['status' => 0, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
            throw new OmgException(OmgException::API_FAILED);
        }
    }

    static function sendMsg($userId) {
        $userInfo = Func::getUserBasicInfo($userId);
        $name = isset($userInfo['realname']) ? $userInfo['realname'] : '用户';
        $template = "亲爱的{$name}，在“交易额破百亿 全民大狂欢”活动中，您获得的随机现金奖励已到账，请登录您的网利宝账户进行查看。";
        SendMessage::Mail($userId, $template, []);
        SendMessage::Message($userId, $template, []);
    }

}
