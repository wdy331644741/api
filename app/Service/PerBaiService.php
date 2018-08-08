<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use Config, DB;


class PerBaiService
{
    const PERBAI_VERSION = 1;
    //用户随机中奖号码发放
    public  static function addDrawNum($userId, $number, $type='investment')
    {
        if ($type == 'investment') {
            self::addDrawNumByInvestment($userId, $number, $type);
        } else if ($type == 'invite') {
            self::addDrawNumByInvite($userId, $number, $type);
        }
    }

    public static function addDrawNumByInvestment($userId, $number, $type)
    {
        $config = Config::get('perbai');
        $awards = $config['awards'];
        Attributes::increment($userId, $config['drew_user_key'], $number);
        try {
            DB::beginTransaction();
            Attributes::getItemLock($userId, $config['drew_user_key']);

            //循环插入用户id和抽奖号码
            $info = HdPerbai::select('id', 'draw_number')->where(['user_id' => 0, 'status' => 0, 'period'=>self::PERBAI_VERSION])->take($number)->get()->toArray();
//            var_dump($info);die;
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
                        $update['uuid'] = $uuid = 'wlb' . date('Ydm') . rand(1000, 9999);
                        $update['status'] = 2;
                    } else if ( 0 === ($draw_number%100) ) {
                        $update['award_name'] = $awards['puzhao']['name'];
                        $update['alias_name'] = $awards['puzhao']['alias_name'];
                        $update['uuid'] = $uuid = 'wlb' . date('Ydm') . rand(1000, 9999);
                        $update['status'] = 2;
                    } else if ( $draw_number === ($last_number - 1) ) {
                        $update['award_name'] = $awards['yichuidingyin']['name'];
                        $update['alias_name'] = $awards['yichuidingyin']['alias_name'];
                        $update['uuid'] = $uuid = 'wlb' . date('Ydm') . rand(1000, 9999);
                        $update['status'] = 2;
                    }
                    $update['created_at'] = date('Y-m-d H:i:s');
                    HdPerbai::where(['id' => $v['id']])->update($update);
                }
                $count = count($info);
                Attributes::increment($userId, $config['drew_total_key'], $count);
                Attributes::decrement($userId, $config['drew_user_key'], $count);
            }
            //事务提交结束
            DB::commit();
        } catch (Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] userId: ' . $userId . ' number:' . $number . ' error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_perbai.sql.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            DB::rollBack();
        }
    }
    // 邀请好友 每天上限50个号码
    public static function addDrawNumByInvite($userId, $number, $type)
    {
        $config = Config::get('perbai');
        $awards = $config['awards'];
        //1.判断用户邀请得到的抽奖号的数量 ， >=50,  就不能得到了，每天
        $where = ['user_id' => $userId, 'period'=>self::PERBAI_VERSION, 'type'=>$type];
        $limit_count = HdPerbai::where($where)->whereRaw( " to_days(updated_at) = to_days(now())")->count();
        if ($limit_count >= 50) {
            return false;
        }
        Attributes::increment($userId, $config['drew_user_key'], $number);
        try {
            DB::beginTransaction();
            Attributes::getItemLock($userId, $config['drew_user_key']);
            $info = HdPerbai::select('id', 'draw_number')->where(['user_id' => 0, 'status' => 0, 'period'=>self::PERBAI_VERSION])->first();
            if (!$info) {
                return false;
            }
            $per_config = HdPerHundredConfig::where(['status'=>1])->orderBy('id', 'desc')->first();
            if (!$per_config) {
                throw new OmgException(OmgException::NO_DATA);
            }
            $last_number = $per_config->numbers;
            $draw_number = intval($info['draw_number']);
            $update = ['user_id' => $userId, 'status'=>1, 'type'=>$type];
            if ( 0 === $draw_number) {
                $update['award_name'] = $awards['yimadangxian']['name'];
                $update['alias_name'] = $awards['yimadangxian']['alias_name'];
                $update['uuid'] = 'wlb' . date('Ydm') . rand(1000, 9999);
                $update['status'] = 2;
            } else if ( 0 === ($draw_number%100) ) {
                $update['award_name'] = $awards['puzhao']['name'];
                $update['alias_name'] = $awards['puzhao']['alias_name'];
                $update['uuid'] = 'wlb' . date('Ydm') . rand(1000, 9999);
                $update['status'] = 2;
            } else if ( $draw_number === ($last_number - 1) ) {
                $update['award_name'] = $awards['yichuidingyin']['name'];
                $update['alias_name'] = $awards['yichuidingyin']['alias_name'];
                $update['uuid'] = 'wlb' . date('Ydm') . rand(1000, 9999);
                $update['status'] = 2;
            }
            $update['created_at'] = date('Y-m-d H:i:s');
            HdPerbai::where(['id' => $info['id']])->update($update);
            Attributes::increment($userId, $config['drew_total_key'], $number);
            Attributes::decrement($userId, $config['drew_user_key'], $number);
        //事务提交结束
        DB::commit();
        } catch (Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] userId: ' . $userId . ' number:' . $number . ' error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_perbai.sql.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            DB::rollBack();
        }
    }
    public static function curlSina() {

            $url = "http://hq.sinajs.cn/rn=1533632801221&list=s_sz399001";
            // 创建一个新cURL资源
            $ch = curl_init();
            // 设置URL和相应的选项
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
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
}