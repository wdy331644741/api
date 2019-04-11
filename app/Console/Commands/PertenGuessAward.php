<?php

namespace App\Console\Commands;

use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use App\Models\HdPertenStock;
use App\Service\PerBaiService;
use Illuminate\Console\Command;
use App\Jobs\PertenGuessJob;
use Illuminate\Foundation\Bus\DispatchesJobs;

class PertenGuessAward extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PertenGuessAward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '天天猜大盘最后一次开奖';

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
            $activity = HdPerHundredConfig::where(['status' => 1])->first();
            //活动不存在
            if (!$activity) {
                throw new \Exception('活动不存在');
            }
            //最后一个号码的时间，上一个开盘日的下午三点后
            $curr_num = HdPerbai::where(['period'=>$activity['id']])->orderBy('id', 'desc')->first();
            if ( !$curr_num || $curr_num['user_id'] == 0) {
                throw new \Exception('号码不存在或号码没发完');
            }
            $stocked = HdPertenStock::where(['open_status'=>2, 'period'=>$activity['id']])->first();
            if ($stocked) {
                throw new \Exception('奖励已发完');
            }
            //最后一个号码的时间，上一个开盘日的下午三点后
            $updated_at = strtotime($curr_num['updated_at']);
            $last_stock = HdPertenStock::where(['open_status'=>1, 'period'=>$activity['id']])->orderBy('id', 'desc')->first();
            $last_time = $last_stock['curr_time'];
            if ( $updated_at <= strtotime("$last_time 15:00:00") ) {
                throw new \Exception('奖励发放完');
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
            $change_status = $change >= 0 ? 1 : 2;
            $ret = HdPertenStock::create([
                'period'=> $activity['id'],
                'stock'=> $price,
                'change'=> $change,
                'change_status'=> $change_status,
                'draw_number'=> $draw_number,
                'curr_time'=> $stock_time,
                'open_status'=> 2,
            ]);
            if (!$ret) {
                throw new \Exception('Database error');
            }
            //天天猜发奖
            $this->dispatch(new PertenGuessJob($change_status));
            return false;
        } catch (\Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] crontab error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'console.PertenGuessAward.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            return false;
        }
    }
}
