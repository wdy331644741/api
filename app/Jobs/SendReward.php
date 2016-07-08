<?php

namespace App\Jobs;

use App\Models\Award;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Award6;
use App\Models\Coupon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendAward;


class SendReward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $activityID;
    private $userID;
    private $triggerName;
    private $money;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($activityID,$userID,$triggerName)
    {
        $this->activityID = intval($activityID);
        $this->userID = intval($userID);
        $this->triggerName = $triggerName;
        $this->money = 1000;//投资金额以后会用触发
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //判断规则是否符合
        $rule = true;
        //查找奖品发送
        if ($rule) {
            //符合
            //查询全部奖品
            $awardList = $this->awardList($this->activityID);
            //是否存在奖品
            if($awardList && !empty($awardList)){
                //是否有匹配的奖品
                $awardArray = array();
                if(count($awardList) >= 1){
                    foreach($awardList as $k => $item){
                        if($item['name'] === $this->triggerName){
                            $awardArray[$k]['award_type'] = $item['award_type'];
                            $awardArray[$k]['award_id'] = $item['award_id'];
                        }
                    }
                    //调用发送奖励接口
                    if(!empty($awardArray) && count($awardArray) >= 1){
                        //刘奇接口
                        foreach($awardArray as $val){
                            SendAward::sendDataRole($this->userID,$val['award_type'],$val['award_id'],$this->activityID);
                        }
                    }
                }
            }
        }
    }
    /**
     * 获取奖品映射关系列表
     * @param $activityID
     * @return \Illuminate\Http\JsonResponse
     */
    function awardList($activityID){
        //活动ID
        $where['activity_id'] = intval($activityID);
        if(empty($where['activity_id'])){
            return '';
        }
        $list = Award::where($where)->orderBy('updated_at','desc')->get()->toArray();
        foreach($list as &$item){
            $table = $this->_getAwardTable($item['award_type']);
            $info = $table::where('id',$item['award_id'])->select()->get()->toArray();
            if(count($info) >= 1 && isset($info[0]['name'])){
                $item['name'] = $info[0]['name'];
                $item['info'] = $info[0];
                $item['info']['award_type'] = $item['award_type'];
            }else{
                $item['name'] = '';
                $item['info'] = array();
                $item['info']['award_type'] = 0;
            }
        }
        return $list;
    }
    /**
     * 获取表对象
     * @param $awardType
     * @return Award1|Award2|Award3|Award4|Award5|Award6|bool
     */
    function _getAwardTable($awardType){
        if($awardType >= 1 && $awardType <= 6) {
            if ($awardType == 1) {
                return new Award1;
            } elseif ($awardType == 2) {
                return new Award2;
            } elseif ($awardType == 3) {
                return new Award3;
            } elseif ($awardType == 4) {
                return new Award4;
            } elseif ($awardType == 5) {
                return new Award5;
            } elseif ($awardType == 6){
                return new Coupon;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
}
