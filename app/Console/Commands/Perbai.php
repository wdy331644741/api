<?php

namespace App\Console\Commands;

use App\Models\HdPerbai;
use App\Service\GlobalAttributes;
use App\Service\PerBaiService;
use Illuminate\Console\Command;
use Config;

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
        $key = PerBaiService::PERBAI_VERSION_END;

        $attr = GlobalAttributes::getItem($key);
        if ($attr && $attr['number'] == 0) {
            $price = PerBaiService::curlSina();
            if ($price) {
                $price = $price * 100;
                GlobalAttributes::setItem($key, $price, date('Y-d-m'), '已抓取完成');
                //开奖号码
                $draw_number = substr(strrev($price), 0, 4);
                $config = Config::get('perbai');
                $awards = $config['awards']['zhongjidajiang'];
                $update['award_name'] = $awards['name'];
                $update['alias_name'] = $awards['alias_name'];
                $update['uuid'] = 'wlb' . date('Ydm') . rand(1000, 9999);
                $update['status'] = 2;
                HdPerbai::where(['draw_number'=>$draw_number, 'period'=>PerBaiService::PERBAI_VERSION])->update($update);

            }
        }
    }
}
