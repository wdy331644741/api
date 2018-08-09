<?php

namespace App\Console;

use App\Service\GlobalAttributes;
use App\Service\PerBaiService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\newThreadIcon;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        Commands\VoteAwardDebug::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {   
        // $filePath = storage_path('logs/vote.log');
        // $schedule->command('VoteAwardDebug')
        //          ->hourly()->withoutOverlapping()->sendOutputTo($filePath);

        //逢百抽大奖抓取深证成指数
        $schedule->call(function(){
            //code
            $price = PerBaiService::curlSina();
            $price = $price * 100;
            $key = PerBaiService::PERBAI_VERSION_END . PerBaiService::PERBAI_VERSION;
            GlobalAttributes::setItem($key, $price);
        })->weekdays()->at('15:01')->where(function(){
            //condition
            $key = PerBaiService::PERBAI_VERSION_END . PerBaiService::PERBAI_VERSION;
            $attr = GlobalAttributes::getItem($key);
            if ($attr && $attr['number'] == 0) {
                return true;
            }
            return false;
        });

    }
}
