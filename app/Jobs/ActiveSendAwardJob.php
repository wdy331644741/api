<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendAward;

class ActiveSendAwardJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $user_id;
    private $aliasName; //活动别名

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id ,$aliasName)
    {
        $this->user_id   = $user_id;
        $this->aliasName = $aliasName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return SendAward::ActiveSendAward($this->user_id , $this->aliasName);
    }

}
