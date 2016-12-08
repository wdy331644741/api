<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;


use App\Models\MoneyShareInfo;
use App\Models\MoneyShare;
use App\Service\MoneyShareBasic;
use App\Service\SendAward;
class ReissueMoneyShare extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $unSendList = MoneyShareInfo::where('status',0)->take(100)->get();
        //循环发奖
        foreach ($unSendList as $item){
            if(empty($item['award_id']) || empty($item['award_type']) || empty($item['user_id']) || empty($item['id'])){
                continue;
            }
            $isExist = array();
            $isExist['id'] = $item['main_id'];
            $mallInfo = MoneyShare::where($isExist)->first();
            if(empty($mallInfo)){
                continue;
            }
            $awardTableObj = SendAward::_getAwardTable($mallInfo['award_type']);
            $awardInfo = $awardTableObj->where('id', $mallInfo['award_id'])->first();
            if(empty($awardInfo)){
               continue;
            }
            $awardInfo->user_id = $item['user_id'];
            $awardInfo->unSendID = $item['id'];
            $awardInfo->amount = $item['money'];
            MoneyShareBasic::formatData($mallInfo,$awardInfo);
        }
        return true;
    }
}
