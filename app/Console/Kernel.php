<?php

namespace App\Console;

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
        Commands\Perbai::class,
        Commands\SendPush::class,
        Commands\PertenRemind::class,
        Commands\PertenGuess::class,
        Commands\PertenGuessAward::class,
        Commands\PertenGuessActStatus::class,
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

        //逢10抽大奖15:30开奖
        $schedule->command('Perbai')->weekdays()->at("15:30");
        //天天猜大盘13:00查看逢十号码是否发送完
        $schedule->command('Perbai')->weekdays()->at("13:00");
        //逢10活动前10分钟提醒
        $schedule->command('SendPush')->withoutOverlapping();
        //每日14:00全量PUSH提醒获得抽奖号码用户
        $schedule->command('PertenRemind')->dailyAt("14:00");
        //每日 11:00 全量提醒获得抽奖号码用户（大盘预言）
        $schedule->command('PertenGuess')->dailyAt("11:00");
        //天天猜最后一次开奖
        $schedule->command('PertenGuessAward')->weekdays()->at("15:30");
    }
}
