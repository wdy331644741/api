<?php

namespace App\Jobs;
use App\Service\Attributes;
use App\Service\NetworkDramaDzpService;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;


class NetworkDramaDzpBatch extends Job implements ShouldQueue
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
        NetworkDramaDzpService::sendAward($this->userId, $this->awards);
    }
}
