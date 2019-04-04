<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\HdPertenGuess;
use App\Models\HdPertenGuessLog;
use App\Models\HdPertenStock;
use App\Service\GlobalAttributes;
use App\Models\GlobalAttribute;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use Config, Cache,DB;
use Lib\JsonRpcClient;


class PerBaiService
{
    public static $nodeType = 'perten';//push提醒type
    public static $guessKeyInvite = 'perten_guess_invite';//push提醒type
    public static $guessKeyUser = 'perten_guess_user';//天天猜注数
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
                    $url = "https://". env('ACCOUNT_BASE_HOST') . '/wechat/address';
                    $msg = "亲爱的用户，恭喜您在逢 10 股指活动中获得首投实物大奖". $activityConfig->first_award ."，请于当期活动结束后及时联系平台客服人员兑换奖品。温馨提示：确保您在网利宝平台的收货地址准确无误，立即完收货地址：{".$url."}，客朋电话：400-858-8066。";
                    self::sendMessage($userId, $msg);
                    $push = "亲爱的用户，恭喜您在逢 10 股指活动中获得首投实物大奖". $activityConfig->first_award ."，立即查看。";
//                    SendMessage::sendPush($userId ,'test', $push);
                    //逢10奖
                } else if ( 0 === ($draw_number%10) ) {
                    $update['award_name'] = $activityConfig->sunshine_award;
                    $update['status'] = 2;
                    $activityJd = ActivityService::GetActivityedInfoByAlias('perten_jingdongka');
                    SendAward::addAwardByActivity($userId, $activityJd->id);
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
            $guessNum = count($info);
            $guessKey = self::$guessKeyUser . $activityConfig->id;
            Attributes::increment($userId, $guessKey, $guessNum * 5);
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
            if (!$url) {
                return false;
            }
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
            '2019-05-01', '2019-06-07', '2019-09-13', '2019-10-01','2019-10-02','2019-10-03','2019-10-04','2019-10-07'
        ];
    }

    public static function getStockNextTime()
    {

    }

    public static function sendAward($userId, $money)
    {
        $stockPush = "亲爱的用户，恭喜您在逢 10 股指活动中获得股指现金大奖 {{awardname}} 元现金，现金已发放至您账户余额，立即查看。";
        $uuid = SendAward::create_guid();
        //发送接口
        $result = Func::incrementAvailable($userId,999999,$uuid, $money,'stock_index_cash');
        $return = ['award_name'=> $money, 'status'=> true];
        //发送消息&存储到日志
        if (isset($result['result']['code']) && $result['result']['code'] == 0) {//成功
            $url = "https://". env('ACCOUNT_BASE_HOST') . '/';
            $stockTemple = "亲爱的用户，恭喜您在逢 10 股指活动中获得股指现金大奖 ".$money." 元现金，现金已发放至您账户余额，点击查看{".$url."}。";
            PerBaiService::sendMessage($userId, $stockTemple);
            $stockPush = "亲爱的用户，恭喜您在逢 10 股指活动中获得股指现金大奖 ".$money." 元现金，现金已发放至您账户余额，立即查看。";
            SendMessage::sendPush($userId, 'node', $stockPush);
        }else{//失败
            $return = array('award_name'=> $money, 'status'=>false,'err_data'=>$result);
        }
        return $result;
    }

    public static function addGuessNumber($userId, $number)
    {
        $activity = self::getActivityInfo();
        //1.判断用户邀请得到的抽奖号的数量 ， >=50,  就不能得到了，每天
        $max = Attributes::getNumberByDay($userId, self::$guessKeyInvite);
        if ($max >= 100) {
            return false;
        }
        if ( ($max + $number) >= 100 ) {
            $number = 100 - $max;
        }
        Attributes::incrementByDay($userId, self::$guessKeyInvite, $number);
        $guessKey = self::$guessKeyUser . $activity['id'];
        Attributes::increment($userId, $guessKey, $number);
    }

    //天天猜发奖
    public static function guessSendAward($type)
    {
        $activity = self::getActivityInfo();
        $totalMoney = $activity['guess_award'];
        if ($totalMoney <= 0) {
            return false;
        }
        $period = $activity['id'];
        //总次数
        $totalCount = HdPertenGuess::select(DB::raw('SUM(number) as total'))->where(['period'=>$period, 'type'=>$type, 'status'=>0])->value('total');
        $totalCount = intval($totalCount);
        if (!$totalCount) {
            return false;
        }
        $data = HdPertenGuess::select('user_id', DB::raw('SUM(number) as total'))->where(['period'=>$period, 'type'=>$type, 'status'=>0])->groupBy('user_id')->get()->toArray();
        if (!$data) {
            return false;
        }
        $url = "https://". env('ACCOUNT_BASE_HOST') . '/';
        $msgTempl = "亲爱的用户，恭喜您在天天猜大盘涨跌活动中赢得瓜分体验金金额：{{money}}元，今日已可以预言明日大盘结果，立即去查看{".$url."}。";
        $pushTempl="亲爱的用户，恭喜您在天天猜大盘涨跌活动中赢得瓜分体验金金额：{{money}}元，今日已可以预言明日大盘结果，立即去查看。";
        $totalPeople = count($data);
        foreach ($data as $v) {
            $money = bcdiv(bcmul($totalMoney, $v['total'], 2), $totalCount, 2);
            $guessLog = new HdPertenGuessLog();
            $guessLog->user_id = $v['user_id'];
            $guessLog->period = $period;
            $guessLog->money = $money;
            $tplParam = [
                'money'=>$money,
            ];
            $result = self::experience($v['user_id'], $money);
            if ($result === true) {
                $guessLog->status = 1;
            } else {
                $guessLog->remark = $result;
            }
            $guessLog->save();
            SendMessage::Mail($v['user_id'], $msgTempl, $tplParam);
            SendMessage::Message($v['user_id'], $msgTempl, $tplParam);
            //todo
//            SendMessage::sendPush($v['user_id'], 'test',$pushTempl);
        }
        HdPertenGuess::where(['period'=>$period, 'status'=>0])->update(['status'=>1]);
        return true;
    }

    public static function experience($userId, $money)
    {
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        $uuid = SendAward::create_guid();
        //体验金
        $data['user_id'] = $userId;
        $data['uuid'] = $uuid;
        $data['source_id'] = 999999;
        $data['name'] = $money . "体验金";
        //体验金额
        $data['amount'] = $money;
        $data['effective_start'] = date("Y-m-d H:i:s");
        $data['effective_end'] = date("Y-m-d H:i:s", strtotime("+7 days"));
        $data['source_name'] = "天天猜大盘涨跌";
        //发送接口
        $result = $client->experience($data);
        //发送消息&存储到日志
        if (isset($result['result']) && $result['result']) {//成功
            return true;
        } else {//失败
            //记录错误日志
            $err = array('award_name' => $data['name'], 'err_data' => $result, 'url' => $url);
            return $err;
        }
    }
}