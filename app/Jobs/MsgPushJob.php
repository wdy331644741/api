<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendMessage;

class MsgPushJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $user_id;
    private $SendMessage_type; //mail;mess;push
    private $SendMessage_content;//文案模版
    private $arr;//替换 关键字
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id ,$SendMessage_content,$SendMessage_type ,$arr = array() )
    {
        $this->user_id             = $user_id;
        $this->SendMessage_type    = $SendMessage_type;
        $this->SendMessage_content = $SendMessage_content;
        $this->arr                 = $arr;
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
                $res = SendMessage::Mail($this->user_id,$this->SendMessage_content);
                break;
            case 'push':
                $res = SendMessage::sendPushTem($this->user_id,$this->SendMessage_content);
                break;
            case 'mess':
                // SendMessage::Message($this->user_id,$this->SendMessage_content);
                break;
            
            default:
                # code...
                break;
        }

        return $res;
    }

}
