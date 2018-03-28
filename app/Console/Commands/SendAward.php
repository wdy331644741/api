<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Redis;

use App\Models\SignInSystem;

class SendAward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature ='sendAward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '签到发奖';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $awardKey ='shakeSendRewardList';

        $length = REDIS::LLEN($awardKey);//获取队列长度
        $http = 0;
        $httpFalut =0 ;
        $httpSuccess =0;
        $isSuccess =false;
        while ($length >0){
            //主循环取队列数据
                $info = json_decode(REDIS::RPOP($awardKey));
                if(!$info){
                    break;
                }
                switch ($info['is_rmb']){ //分类发奖
                    case 1://现金奖励

                        $res = Func::incrementAvailable($info['user_id'], $info['rec_id'], $info['uuid'], $info['amount'],$info['amount_type']);
                        $remark['addMoneyRes'] = $res;
                        if(isset($res['result'])){
                            $isSuccess = true;
                        }

                    break;
                    case 0 ://体验金
                        $res = SendAward::ActiveSendAward($info['user_id'], $info['awardName']);
                        $remark['awards'] = $res;
                        if(isset($awards[0]['award_name']) && !$awards[0]['status']){
                            $isSuccess = true;
                        }

                     break;
                }

                //如果失败 压回队列 继续发送
                if(!$isSuccess) {
                    //REDIS::LPUSH($awardKey,json_encode($info));
                    SignInSystem::update(['status'=>0,'remark'=>json_encode($remark, JSON_UNESCAPED_UNICODE)])->where(['id'=>$info['rec_id']]);
                    $httpFalut++;
                }else {
                    //本地留存记录
                    SignInSystem::update(['status'=>1,'remark'=>json_encode($remark, JSON_UNESCAPED_UNICODE)])->where(['id'=>$info['rec_id']]);
                    $httpSuccess++;
                }
                $http++;
//                if($httpFalut/$http >0.5) {
//                }
            }
            $length--;
            sleep(100);
        }




}
