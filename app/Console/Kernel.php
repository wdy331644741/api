<?php

namespace App\Console;

use App\Console\Commands\SendAwards;
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
        Commands\SendAwards::class,
        Commands\VoteAwardDebug::class,
        Commands\Perbai::class,
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
        $schedule->command('Perbai')->weekdays()->timezone('Asia/Shanghai')->when(function () {
            //15点整抓取不准,过两分开始
            return date('Hi') >= 1530 && date('H') <= 23;
        })->withoutOverlapping();

    }
}
