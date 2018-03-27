<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Redis;


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
        $awardKey ='awardList';

        $length = REDIS::LLEN($awardKey);//获取队列长度
        $http = 0;
        $httpFalut =0 ;
        $httpSuccess =0;
        $isSuccess =false;
        while ($length >0){
            //主循环取队列数据
                $info = json_decode(REDIS::RPOP($awardKey));



                switch ($info['is_rmb']){ //分类发奖
                    case 1://现金奖励
                        $amount = bcmul($info['size'], $info['multiple'] + $info['multipleCard'], 2);
                        $res = Func::incrementAvailable($info['user_id'], '', $info['uuid'], $amount, '');
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
                    REDIS::LPUSH($awardKey,json_encode($info));
                    $httpFalut++;
                }else {
                    //本地留存记录
                    SignInSystem::create([
                        'user_id' => $info['user_id'],
                        'award_name' => $info['award_name'],
                        'uuid' => $info['uuid'],
                        'ip' => $info['ip'],
                        'amount' => $info['size'],
                        'multiple' => $info['multiple'],
                        'multiple_card' => $info['multiple_card'],
                        'user_agent' => $info['user_agent'],
                        'status' => $info['status'],//默认是成功，失败会修改为0
                        'type' => $info['type'],
                        'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
                    ]);
                    $httpSuccess++;
                }
                $http++;
//                if($httpFalut/$http >0.5) {
//                }
            }
            $length = REDIS::LLEN($awardKey);
            sleep(100);
        }




}
