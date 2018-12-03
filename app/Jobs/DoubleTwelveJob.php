<?php

namespace App\Jobs;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Service\DoubleTwelveService;

class DoubleTwelveJob extends Job implements ShouldQueue
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
            DoubleTwelveService::sendAward($this->userId, $this->awards);
    }
}
