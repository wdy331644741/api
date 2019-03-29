<?php

namespace App\Console\Commands;

use App\Models\HdPerbai;
use App\Models\GlobalAttribute;
use App\Models\HdPerHundredConfig;
use App\Models\HdPertenStock;
use App\Service\Func;
use App\Service\GlobalAttributes;
use App\Service\PerBaiService;
use App\Service\SendMessage;
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
            $activity = HdPerHundredConfig::where(['status' => 1])->first();
            //活动不存在
            if (!$activity) {
                return false;
            }
            //活动未开始
            if ( time() < strtotime($activity['start_time']) ) {
                return false;
            }
            //活动已结束 ,号码发完
            if ( 0 >= PerBaiService::getRemainNum() ) {
                return false;
            }
            $stock = PerBaiService::getStockPrice();
            if ($stock === false) {
                return false;
            }
            $stock_time = PerBaiService::getStockTime();
            if ($stock_time === false) {
                return false;
            }
            $price = round($stock[0], 2);
            $change = round($stock[1], 2);
            $draw_number = substr(strrev($price * 100), 0, 4);
            $model = HdPertenStock::where(['curr_time'=>$stock_time])->first();
            if ($model) {
                return false;
            }
            $ret = HdPertenStock::create([
                'period'=> $activity['id'],
                'stock'=> $price,
                'change'=> $change,
                'change_status'=> $change >= 0 ? 1 : 2,
                'draw_number'=> $draw_number,
                'curr_time'=> $stock_time,
            ]);
            if (!$ret) {
                return false;
            }
            $perten = HdPerbai::where(['draw_number'=> $draw_number, 'period'=>$activity['id']])->first();
            if (!$perten || $perten->user_id == 0) {
                return false;
            }
            $perten->award_name = $draw_number;
            $perten->status = 2;
            if ( $perten->save() ) {
                $ret->open_status = 1;
                $remark = PerBaiService::sendAward($perten->user_id, $draw_number);
                $ret->remark = json_encode($remark);
                $ret->save();
            }
            return false;
        } catch (Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] crontab error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'perten.sql.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            return false;
        }
    }
}
