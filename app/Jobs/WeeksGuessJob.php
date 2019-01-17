<?php

namespace App\Jobs;
use App\Models\HdWeeksGuess;
use App\Service\WeeksGuessService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class WeeksGuessJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $totalMoney;
    private $period;
    private $type;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($period, $totalMoney, $type)
    {
        $this->totalMoney = $totalMoney;
        $this->period = $period;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        WeeksGuessService::sendAward($this->period, $this->totalMoney, $this->type);
    }

}
