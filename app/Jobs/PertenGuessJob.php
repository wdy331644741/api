<?php

namespace App\Jobs;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use App\Service\PerBaiService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Cache;

class PertenGuessJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $type;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($type)
    {
        $this->number = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       PerBaiService::guessSendAward($this->type);
    }

}
