<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use Config, Cache,DB;


class PerBaiService
{
    static $perbai_version;//1
    static $perbai_version_end;//'perbai_end_1';

    public function __construct()
    {
        $config = Cache::remember('perbai_config', 5, function(){
            $data = HdPerHundredConfig::where(['status'=>1])->first();
            return $data;
        });
        self::$perbai_version = $config['id'];
        self::$perbai_version_end = 'perbai_end_' . $config['id'];
    }

    //用户随机中奖号码发放
    public  static function addDrawNum($userId, $number, $type='investment')
    {
            $activityConfig = HdPerHundredConfig::where(['status' => 1])->orderBy('id','desc')->first();
            if (!$activityConfig) {
                return false;
            }
            //活动开始时间
            if (time() < strtotime($activityConfig->start_time)) {
                return false;
            }
            $model = new self();
            $model->addDrawNumByInvestment($userId, $number, $type);
    }

    public function addDrawNumByInvestment($userId, $number, $type)
    {
        $global_key = self::$perbai_version_end;
        //判断活动结束了
        $attr = GlobalAttributes::getItem($global_key);
        if ($attr) {
            return false;
        }
        $config = Config::get('perbai');
        $awards = $config['awards'];
        if ($type == 'invite') {
            //1.判断用户邀请得到的抽奖号的数量 ， >=50,  就不能得到了，每天
            $where = ['user_id' => $userId, 'period'=>self::$perbai_version, 'type'=>$type];
            $limit_count = HdPerbai::where($where)->whereRaw( " to_days(updated_at) = to_days(now())")->count();
            if ($limit_count >= 2) {
                return false;
            }
        }
        Attributes::increment($userId, $config['drew_user_key'], $number);
        try {
            DB::beginTransaction();
            Attributes::getItemLock($userId, $config['drew_user_key']);

            //循环插入用户id和抽奖号码
            $info = HdPerbai::select('id', 'draw_number')->where(['user_id' => 0, 'status' => 0, 'period'=>self::$perbai_version])->take($number)->get()->toArray();
//            var_dump($info);die;
            $send_msg = [];
            if ($info) {
                $per_config = HdPerHundredConfig::where(['status'=>1])->orderBy('id', 'desc')->first();
                if (!$per_config) {
                    throw new OmgException(OmgException::NO_DATA);
                }
                $last_number = $per_config->numbers;
                foreach ($info as $v) {
                    $draw_number = intval($v['draw_number']);
                    $update = ['user_id' => $userId, 'status'=>1, 'type'=>$type];
                    if ( 0 === $draw_number) {
                        $update['award_name'] = $awards['yimadangxian']['name'];
                        $update['alias_name'] = $awards['yimadangxian']['alias_name'];
                        $update['uuid'] = 'wlb' . date('Ymd') . rand(1000, 9999);
                        $update['status'] = 2;
                        $temp = [
                            'user_id'=>$userId,
                            'awardname'=>$awards['yimadangxian']['name'],
                            'aliasname'=>$awards['yimadangxian']['award_name'],
                            'code'=>$update['uuid']
                        ];
                        $send_msg[] = $temp;
                    } else if ( 0 === ($draw_number%100) ) {
                        $update['award_name'] = $awards['puzhao']['name'];
                        $update['alias_name'] = $awards['puzhao']['alias_name'];
                        $update['uuid'] = 'wlb' . date('Ymd') . rand(1000, 9999);
                        $update['status'] = 2;
                        $temp = [
                            'user_id'=>$userId,
                            'awardname'=>$awards['puzhao']['name'],
                            'aliasname'=>$awards['puzhao']['award_name'],
                            'code'=>$update['uuid']
                        ];
                        $send_msg[] = $temp;
                    } else if ( $draw_number === ($last_number - 1) ) {
                        $update['award_name'] = $awards['yichuidingyin']['name'];
                        $update['alias_name'] = $awards['yichuidingyin']['alias_name'];
                        $update['uuid'] = 'wlb' . date('Ymd') . rand(1000, 9999);
                        $update['status'] = 2;
                        $temp = [
                            'user_id'=>$userId,
                            'awardname'=>$awards['yichuidingyin']['name'],
                            'aliasname'=>$awards['yichuidingyin']['award_name'],
                            'code'=>$update['uuid']
                        ];
                        $send_msg[] = $temp;
                    }
                    $update['created_at'] = date('Y-m-d H:i:s');
                    HdPerbai::where(['id' => $v['id']])->update($update);
                }
                $count = count($info);
                Attributes::increment($userId, $config['drew_total_key'], $count);
                Attributes::decrement($userId, $config['drew_user_key'], $count);
                //判断抽奖号码已发完
                if ($count < $number) {
                    GlobalAttributes::setItem($global_key, 0);
                } else {
                    $remain_num = HdPerbai::where(['user_id' => 0, 'status' => 0, 'period'=>self::$perbai_version])->first();
                    if (!$remain_num) {
                        GlobalAttributes::setItem($global_key, 0);
                    }
                }
            } else {
                $attr = GlobalAttributes::getItem($global_key);
                if (!$attr) {
                    GlobalAttributes::setItem($global_key, 0);
                }
            }
            if ($send_msg) {
                self::sendMessage($send_msg);
            }
            //事务提交结束
            DB::commit();
        } catch (Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] userId: ' . $userId . ' number:' . $number . ' error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'perbai.sql.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            DB::rollBack();
        }
    }

    public static function curlSina() {
            $url = "http://hq.sinajs.cn/list=s_sz399001";
            // 创建一个新cURL资源
            $ch = curl_init();
            // 设置URL和相应的选项
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            // 抓取URL并把它传递给浏览器
            $data = curl_exec($ch);
            if (curl_errno($ch)) {
                $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_perbai_sina.sql.log');
                file_put_contents($filepath, 'Curl error: ' . curl_error($ch));
                return false;
            }
            $price = substr($data, strpos($data, ',') + 1, 7);
            //关闭cURL资源，并且释放系统资源
            curl_close($ch);
            return $price;
    }

    public static function format($number)
    {
        return sprintf("%04d", $number);
    }

    public static function sendMessage($data)
    {
        $config = Config::get('perbai');
        $template = $config['message'];
//        $url = "https://www.wanglibao.com/memberManage/profile.html";
        $url = "https://www.wanglibao.com/wechat/address";
        foreach ($data as $v) {
            $v['url'] = $url;
            SendMessage::Mail($v['user_id'], $template, $v);
            SendMessage::Message($v['user_id'], $template, $v);
        }
    }
}