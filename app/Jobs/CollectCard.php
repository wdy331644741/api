<?php

namespace App\Jobs;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Service\CollectCardService;

class CollectCard extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $userId;
    private $config;
    private $awards;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId,$config,$awards)
    {
        $this->userId = $userId;
        $this->config = $config;
        $this->awards = $awards;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      //  if (count($this->awards)==count($this->awards, 1)) {
            CollectCardService::sendAward($this->userId, $this->awards);
   //     } else {
   //         foreach ($this->awards as $award) {
    //            CollectCardService::sendAward($this->userId, $award);
   //         }
 //       }
    }
}
