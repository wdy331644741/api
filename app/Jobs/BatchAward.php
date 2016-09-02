<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendAward;
use App\Models\AwardBatch;

class BatchAward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $uids;
    private $award_type;
    private $award_id;
    private $source_name;
    private $batch_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uids,$award_type,$award_id,$source_name,$batch_id)
    {
        $this->uids = $uids;
        $this->award_type = $award_type;
        $this->award_id = $award_id;
        $this->source_name = $source_name;
        $this->batch_id = $batch_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //状态改为正在发奖
        AwardBatch::where('id',$this->batch_id)->update(array('status'=>1));
        //循环发奖
        $uids = explode(',',$this->uids);
        foreach($uids as $item){
            $item = trim($item);
            if(!$item || !is_numeric($item)) {
                continue;
            }
            SendAward::sendDataRole($item, $this->award_type, $this->award_id, 0, $this->source_name,$this->batch_id);
        }
        //状态改为发奖完成
        AwardBatch::where('id',$this->batch_id)->update(array('status'=>2));
    }
}
