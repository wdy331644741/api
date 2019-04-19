<?php

namespace App\Console\Commands;

use App\Service\PerBaiService;
use Illuminate\Console\Command;
use App\Models\HdPerbai;
use App\Service\GlobalAttributes;
use Illuminate\Foundation\Bus\DispatchesJobs;
class PertenGuessActStatus extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PertenGuessActStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '设置号码是否发完';

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
            if (!$activity) {
                throw new \Exception('活动不存在');
            }
            $perbaiNumber = HdPerbai::where(['period'=>$activity['id'], 'status'=>0])->first();
            if (!$perbaiNumber) {
                //今天发出去号码就开奖   $perbaiNumber为null说明号码发完了
                GlobalAttributes::setItem('perten_guess_status' . $activity['id'], 1);
            }
            return false;
        }catch (\Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] crontab error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'console.PertenGuessActStatus.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            return false;
        }
    }
}
