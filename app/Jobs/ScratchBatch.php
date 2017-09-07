<?php

namespace App\Jobs;
use App\Service\Scratch;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;


class ScratchBatch extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $userId;
    private $awards;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId,$awards)
    {
        $this->userId = $userId;
        $this->awards = $awards;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //循环发奖
        foreach($this->awards as $item){
            Scratch::sendAward($this->userId, $item);
        }
    }
}
