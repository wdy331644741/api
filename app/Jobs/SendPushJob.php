<?php

namespace App\Jobs;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Pagination\Paginator;
use App\Service\SendMessage;
class SendPushJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $node;
    private $userIds;
    private $tplParam;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userIds,$node, $tplParam="")
    {
        $this->node = $node;
        $this->userIds = $userIds;
        $this->tplParam = $tplParam;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $ret  = SendMessage::sendPush($this->userIds, $this->node, $this->tplParam);
    }

}
