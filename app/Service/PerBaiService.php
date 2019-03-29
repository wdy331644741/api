<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\HdPertenStock;
use App\Service\GlobalAttributes;
use App\Models\GlobalAttribute;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use Config, Cache,DB;


class PerBaiService
{
    public static $nodeType = 'perten';//push提醒type
    //用户随机中奖号码发放
    public  static function addDrawNum($userId, $number, $type, $createTime)
    {
        //活动配置
        $activityConfig = self::getActivityInfo();
        if (!$activityConfig) {
            return false;
        }
        //活动开始时间
        if (time() < strtotime($activityConfig->start_time)) {
            return false;
        }
        try {
            DB::beginTransaction();
            //循环插入用户id和抽奖号码
            $info = HdPerbai::select('id', 'draw_number')->where(['user_id' => 0, 'status' => 0, 'period'=>$activityConfig->id])->lockForUpdate()->take($number)->get()->toArray();
            //活动结束
            if (empty($info)) {
                return false;
            }
            $msg_flag = [];
            foreach ($info as $v) {
                $draw_number = intval($v['draw_number']);
                $update = [
                    'user_id' => $userId,
                    'status'=>1,
                    'type'=>$type,
                    'period'=>$activityConfig->id,
                    'created_at'=>$createTime,
                    'updated_at'=>$createTime,
                ];
                //首投奖
                if ( 0 === $draw_number) {
                    $update['award_name'] = $activityConfig->first_award;
                    $update['status'] = 2;
                    //站内信，短信，push
                    $url = "https://". env(ACCOUNT_BASE_HOST) . '/wechat/address';
                    $msg = "亲爱的用户，恭喜您在逢 10 股指活动中获得首投实物大奖". $activityConfig->first_award ."，请于当期活动结束后及时联系平台客服人员兑换奖品。温馨提示：确保您在网利宝平台的收货地址准确无误，立即完收货地址：{".$url."}，客朋电话：400-858-8066。";
                    self::sendMessage($userId, $msg);
                    $push = "亲爱的用户，恭喜您在逢 10 股指活动中获得首投实物大奖". $activityConfig->first_award ."，立即查看。";
//                    SendMessage::sendPush($userId ,'test', $push);
                    //逢10奖
                } else if ( 0 === ($draw_number%10) ) {
                    $update['award_name'] = $activityConfig->sunshine_award;
                    $update['status'] = 2;
                    $activity = ActivityService::GetActivityedInfoByAlias('perten_jingdongka');
                    SendAward::addAwardByActivity($userId, $activity->id);
//                    "亲爱的用户，恭喜您在逢 10 股指活动中获得逢 10 倍数大奖：100京东卡，京东卡卡密：****，点击查看{活动链接}";
                    $push = "亲爱的用户，恭喜您在逢 10 股指活动中获得逢 10 倍数大奖：".$activityConfig->sunshine_award ."，立即查看。";
//                    SendMessage::sendPush($userId ,'test', $push);
                } else {
                    //股指开奖了，号码没发出去的情况
                    $stock = HdPertenStock::where(['period'=>$activityConfig->id, 'open_status'=>0, 'draw_number'=>$draw_number])->first();
                    if ($stock) {
                        $remark = self::sendAward($userId, $draw_number);
                        $stock->open_status = 1;
                        $stock->remark = json_encode($remark);
                        $stock->save();
                    }
                }
                $updateRes = HdPerbai::where(['id' => $v['id']])->update($update);
                if (!$updateRes) {
                    throw new OmgException(OmgException::DATABASE_ERROR);
                }
            }
            //事务提交结束
            DB::commit();
        } catch (Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] userId: ' . $userId . ' number:' . $number . ' error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'perten.sql.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            DB::rollBack();
        }
    }

    public static function curlSina($url) {
            // 创建一个新cURL资源
            $ch = curl_init();
            // 设置URL和相应的选项
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            $data = curl_exec($ch);
            if (curl_errno($ch)) {
                $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_perten_sina.sql.log');
                file_put_contents($filepath, 'Curl error: ' . curl_error($ch));
                return false;
            }
            //关闭cURL资源，并且释放系统资源
            curl_close($ch);
            return $data;
    }

    public static function getStockPrice()
    {
        // $url = "http://hq.sinajs.cn/list=s_sz399001";//深证
        $url = "http://hq.sinajs.cn/list=s_sh000001";//上证
        $data = self::curlSina($url);
        if ($data === false) {
            return false;
        }
        $str = mb_substr($data, strpos($data, "=") + 1);
        $ret = explode(',', $str);
//        array(6) {[0]=>string(9) ""上证指数"[1]=>string(9) "3010.6357"[2]=>string(7) "13.5403"[3]=>string(4) "0.45"
//              [4]=>string(7) "2658392"[5]=>string(11)"26912282";}
        return [$ret[1], $ret[3]];
    }

    public static function getStockTime()
    {
        $url = "http://hq.sinajs.cn/list=sh000001";//上证
        $data = self::curlSina($url);
        if ($data === false) {
            return false;
        }
        $rules = '/\d{4}-\d{2}-\d{2}/';
        preg_match($rules, $data, $ret);
        if ($ret) {
            return $ret[0];
        }
        return false;
    }

    public static function format($number)
    {
        return sprintf("%04d", $number);
    }

    public static function sendMessage($userId, $template, $params=[])
    {
        SendMessage::Mail($userId, $template, $params);
        SendMessage::Message($userId, $template, $params);
    }

    public static function getActivityInfo()
    {
        $key = 'perten_config';
        $data = Cache::remember($key, 10, function() {
            $config = HdPerHundredConfig::where('status', 1)->first();
            return $config;
        });
        return $data;
    }

    public static function clearActivityInfo()
    {
        $key = 'perten_config';
        Cache::forget($key);
    }

    //剩余号码量
    public static function getRemainNum() {

        $acvitity = self::getActivityInfo();
        $num = HdPerbai::where(['user_id'=>0, 'status'=>0, 'period'=>$acvitity['id']])->count();
        return $num;
    }

    public static function getStockClose()
    {
//        2019年  劳动节：5月1日（星期三）休市。
//　　（五）端午节：6月7日（星期五）至6月9日（星期日）休市。
//　　（六）中秋节：9月13日（星期五）至9月15日（星期日）休市。
//　　（七）国庆节：10月1日（星期二）至10月7日（星期一）休市。
//    （八）全年周六、周日休市。
        return [
            '20190501', '20190607', '20190913', '20191001','20191002','20191003','20191004','20191006'
        ];
    }

    public static function sendAward($userId, $money)
    {
        $stockPush = "亲爱的用户，恭喜您在逢 10 股指活动中获得股指现金大奖 {{awardname}} 元现金，现金已发放至您账户余额，立即查看。";
        $uuid = create_guid();
        //发送接口
        $result = Func::incrementAvailable($userId,999999,$uuid, $money,'stock');
        $return = ['award_name'=> $money, 'status'=> true];
        //发送消息&存储到日志
        if (isset($result['result']['code']) && $result['result']['code'] == 0) {//成功
            $stockTemple = "亲爱的用户，恭喜您在逢 10 股指活动中获得股指现金大奖 ".$money." 元现金，现金已发放至您账户余额，点击查看{活动链接}。";
            PerBaiService::sendMessage($userId, $stockTemple);
            $stockPush = "亲爱的用户，恭喜您在逢 10 股指活动中获得股指现金大奖 ".$money." 元现金，现金已发放至您账户余额，立即查看。";
            SendMessage::sendPush($userId, 'node', $stockPush);
        }else{//失败
            $return = array('award_name'=> $money, 'status'=>false,'err_data'=>$result);
        }
        return $result;
    }
}