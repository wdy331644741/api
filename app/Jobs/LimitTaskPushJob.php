<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendMessage;

class LimitTaskPushJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $user_id;
    private $SendMessage_type;
    private $SendMessage_content;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id ,$SendMessage_content,$SendMessage_type)
    {
        $this->user_id             = $user_id;
        $this->SendMessage_type    = $SendMessage_type;
        $this->SendMessage_content = $SendMessage_content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->SendMessage_type) {
            case 'mail':
                SendMessage::Mail($this->user_id,$this->SendMessage_content);
                break;
            case 'push':
                // SendMessage::sendPush();//tpye
                break;
            case 'mess':
                // SendMessage::Message($this->user_id,$this->SendMessage_content);
                break;
            
            default:
                # code...
                break;
        }
    }

}
