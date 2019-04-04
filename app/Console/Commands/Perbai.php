<?php

namespace App\Console\Commands;

use App\Jobs\PertenGuessJob;
use App\Models\HdPerbai;
use App\Models\GlobalAttribute;
use App\Models\HdPerHundredConfig;
use App\Models\HdPertenStock;
use App\Service\Func;
use App\Service\GlobalAttributes;
use App\Service\PerBaiService;
use App\Service\SendMessage;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Config, DB;

class Perbai extends Command
{
    use DispatchesJobs;
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
    protected $description = '抓取上证成指数';

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
        try {
            $activity = PerBaiService::getActivityInfo();
            //活动不存在
            if (!$activity) {
                throw new \Exception('活动不存在');
            }
            //活动未开始
            if ( time() < strtotime($activity['start_time']) ) {
                throw new \Exception('活动未开始');
            }
            //今天发出去号码就开奖
            $curr_num = HdPerbai::where(['period'=>$activity['id']])->where('status', '>', 0)->whereRaw(" to_days(updated_at) = to_days(now()) ")->first();
            if ( !$curr_num ) {
                throw new \Exception('号码昨天已发完');
            }
            $stock = PerBaiService::getStockPrice();
            if ($stock === false) {
                throw new \Exception('curl error');
            }
            $stock_time = PerBaiService::getStockTime();
            if ($stock_time === false) {
                throw new \Exception('curl error');
            }
            $price = round($stock[0], 2);
            $change = round($stock[1], 2);
            $draw_number = substr(strrev($price * 100), 0, 4);
            $model = HdPertenStock::where(['curr_time'=>$stock_time])->first();
            if ($model) {
                throw new \Exception('当前日期已抓取'.$stock_time);
            }
            $ret = new HdPertenStock();
            $ret->period = $activity['id'];
            $ret->stock = $price;
            $ret->change = $change;
            $ret->change_status = $change >= 0 ? 1 : 2;
            $ret->draw_number = $draw_number;
            $ret->curr_time = $stock_time;
            if (!$ret->save()) {
                throw new \Exception('Database error');
            }
            $perten = HdPerbai::where(['draw_number'=> $draw_number, 'period'=>$activity['id']])->first();
            if (!$perten || $perten->user_id == 0) {
                throw new \Exception('中奖号码没发出去');
            }
            $perten->award_name = $draw_number;
            $perten->status = 2;
            if ( $perten->save() ) {
                $ret->open_status = 1;
                $remark = PerBaiService::sendAward($perten->user_id, $draw_number);
                $ret->remark = json_encode($remark);
                $ret->save();
            }
            //天天猜发奖
            $this->dispatch(new PertenGuessJob($change));
            return false;
        } catch (\Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] crontab error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'console.Perbai.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            return false;
        }
    }
}
