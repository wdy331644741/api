<?php

namespace App\Console\Commands;

use App\Models\HdPerbai;
use App\Models\GlobalAttribute;
use App\Service\GlobalAttributes;
use App\Service\PerBaiService;
use Illuminate\Console\Command;
use Config, DB;

class Perbai extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Perbai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取深证成指数';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return false;
        $perbaiService = new PerBaiService();
        $key = $perbaiService::$perbai_version_end;

        DB::beginTransaction();
        $attr = GlobalAttribute::where(array('key' => $key))->lockforupdate()->first();
        //次日开奖
        $today = date('Ymd', time());
        $oldday = date('Ymd', strtotime($attr['created_at']));
        if ($oldday >= $today) {
            return false;
        }
        if ($attr && $attr['number'] == 0) {
            $price = PerBaiService::curlSina();
            if ($price) {
                $price = $price * 100;
                try {
                    GlobalAttributes::setItem($key, $price, date('Y-m-d'), '已抓取完成');
                    //开奖号码
                    $draw_number = substr(strrev($price), 0, 4);
                    $config = Config::get('perbai');
                    $awards = $config['awards']['zhongjidajiang'];
                    $update['award_name'] = $awards['name'];
                    $update['alias_name'] = $awards['alias_name'];
                    $update['uuid'] = 'wlb' . date('Ymd') . rand(1000, 9999);
                    $update['status'] = 2;
                    $where = [
                        'draw_number'=>$draw_number,
                        'period'=>$perbaiService::$perbai_version
                    ];
                    $perbai_model = HdPerbai::where($where)->first();
                    HdPerbai::where($where)->update($update);
                    DB::commit();
                    $sendData = [
                        'user_id'=>$perbai_model->user_id,
                        'awardname'=>$awards['name'],
                        'aliasname'=>$awards['award_name'],
                        'code'=>$update['uuid']
                    ];
                    PerBaiService::sendMessage(array($sendData));
                } catch (Exception $e) {
                    $log = '[' . date('Y-m-d H:i:s') . '] crontab error:' . $e->getMessage() . "\r\n";
                    $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'perbai.sql.log');
                    file_put_contents($filepath, $log, FILE_APPEND);
                    DB::rollBack();
                }
            }
        }
    }
}
