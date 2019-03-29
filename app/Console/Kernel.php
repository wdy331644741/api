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

        //逢10抽大奖抓取深证成指数
        $schedule->command('Perbai')->weekdays()->withoutOverlapping()->timezone('Asia/Shanghai')->when(function () {
            return date('Hi') >= 1530;
        });
        $schedule->command('SendPush')->withoutOverlapping();

    }
}
