<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\InviteLimitTask;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\SendAward;
use App\Models\GlobalAttribute;
use App\Models\Activity;

use Lib\JsonRpcClient;

use DB, Config;

class InviteTaskService
{
    const TASK_ID = array(
                        'invite_limit_task_exp',
                        'invite_limit_task_bind',
                        'invite_limit_task_invest'
                    );
    const INVITE_LIMIT_TASK = 'invite_limit_task';//后台全局变量 设置项
    const INVITE_LIMIT_TASK_RESET = 'invite_limit_task_reset';

    const EXP_BYDAY = 'invite_limit_task_exp';//每日 限购100份
    const BIND_BYDAY = 'invite_limit_task_bind';
    const RECHARGE_BYDAY = 'invite_limit_task_invest';

    public $user_id = 0;

    public $whitch_tasks = '';//获取是哪一天的任务批次
    public $whitch_tasks_start = '';//获取是哪一天的任务批次 开始时间
    public $tasks_total = array();//每天各任务总数
    public $tasks_limit_time = array();
    public $invite_limit_task_reset = 0;//每天重置任务时间
    /**
     *  构造 从数据库中获取  好友邀请3.0的配置
     */
    public function __construct($userId = 0)
    {
        $this->user_id = $userId;
        $Config = GlobalAttributes::getItems(self::INVITE_LIMIT_TASK)->ToArray();
        $this->tasks_total = array_column($Config,'number','string');
        $this->tasks_limit_time = array_column($Config,'text','string');
        $this->invite_limit_task_reset = GlobalAttributes::getString(self::INVITE_LIMIT_TASK_RESET ,0);
        $this->whitch_tasks_start = $this->getAutoTime();
        $this->whitch_tasks = date('YmdH' , strtotime($this->whitch_tasks_start) );
    }
    public function addTaskByUser($alias_name)
    {
        if (!$this->user_id || !$alias_name ) {
            return false;
        }

        //是否可再领取
        // $locked = GlobalAttributes::getNumberByTodaySeconds(self::EXP,0,3600*$this->invite_limit_task_reset );
        // return $locked;
        if(!$this->isInsertTaskData($alias_name) ){
            return false;
        }

        //记录添加
        InviteLimitTask::create([
            'user_id'=> $this->user_id,
            'alias_name'=> $alias_name,
            'status'=> 0,
            'date_str'=> $this->whitch_tasks,
            'limit_time'=> $this->getTaskLimitTime($alias_name , $this->whitch_tasks_start)//领取任务的超时时间
        ]);
        return true;
    }

    private function isInsertTaskData($alias_name){
        $eloquent = $this->userTaskDataByDay($this->user_id)->where('alias_name',$alias_name);
        //是否已经完成该任务
        $isDone = $eloquent->where('status' ,1);
        if($isDone->isEmpty()){
            //是否正在进行任务
            $doing = $eloquent->filter(function ($value,$key){
                return $value['limit_time'] > date('Y-m-d H:i:s');
            });
            //是否剩余可领取到任务
            if($doing->isEmpty()){
                return true;
            }
        }else{
            return false;
        }
    }

    private function userTaskDataByDay(){
        return InviteLimitTask::where(['user_id'=>$this->user_id , 'date_str'=>$this->whitch_tasks])
        // ->where('limit_time', '>', date('Y-m-d H:i:s'))
        ->get();
    }

    //所有任务开始的时间
    private function getAutoTime(){
        //独立日
        if(date('Y-m-d H:i:s') > date("Y-m-d $this->invite_limit_task_reset:00:00") ){
            $start_task = date("Y-m-d $this->invite_limit_task_reset:00:00");
            // $end_task = date('Y-m-d 11:00:00',strtotime('+1 day') );
        }else{
            $start_task = date("Y-m-d $this->invite_limit_task_reset:00:00",strtotime('-1 day') );
            // $end_task = date('Y-m-d 11:00:00');
        }
        return $start_task;
    }

    //限时任务所有任务均在每日 11 点整点重置为默认值。所有任务倒计时时间
    //均以次日 11 点为优先计算，如倒计时时间距离次日 11 点＜任务周期时间，则
    //显示距离 11 点时间为倒计时时间
    private function getTaskLimitTime($alias_name ,$start_time){
        //该批次任务 过期时间
        $limit_time = strtotime("+1 day",strtotime($start_time));
        //限时任务所有任务均在每日 11 点整点重置为默认值。所有任务倒计时时间
        //均以次日 11 点为优先计算，如倒计时时间距离次日 11 点＜任务周期时间，则
        //显示距离 11 点时间为倒计时时间
        switch ($alias_name) {
            case 'invite_limit_task_exp':
                $time = ($limit_time - time() )>$this->tasks_limit_time['invite_limit_task_exp'] ? time()+$this->tasks_limit_time['invite_limit_task_exp'] :$limit_time;
                break;
            case 'invite_limit_task_bind':
                $time = ($limit_time - time() )>$this->tasks_limit_time['invite_limit_task_bind'] ? time()+$this->tasks_limit_time['invite_limit_task_bind'] :$limit_time;
                break;
            case 'invite_limit_task_invest':
                $time = $limit_time;
                break;
            default:
                # code...
                break;
        }
        return date('Y-m-d H:i:s' , $time);
    }

    //分享成功rpc 调用 发奖
    public function updateExpTask(){
        //是否正在 做任务
        $_data = $this->userTaskDataByDay($this->user_id)
                ->where('alias_name' ,'invite_limit_task_exp')
                ->where('status' ,0)
                ->filter(function ($value,$key){
                    return $value['limit_time'] > date('Y-m-d H:i:s');
                });

        //存在一条正在做的任务
        if($_data->count() == 1){
            //事务开始
            DB::beginTransaction();
            // $locked = GlobalAttributes::getNumberByTodaySeconds(self::EXP,0,3600*$this->invite_limit_task_reset );
            $locked = GlobalAttribute::where(array('key' => self::EXP_BYDAY.$this->whitch_tasks) )
                    ->lockforupdate()->first();
            if(!$locked) {//如果不存在 新建一个 并锁住
                $new_locked_id = GlobalAttribute::create(['key' => self::EXP_BYDAY.$this->whitch_tasks,  'number' => 0]);
                $locked = GlobalAttribute::where(array('id' => $new_locked_id->id) )
                    ->lockforupdate()->first();
            }
            if($locked->number >= $this->tasks_total['invite_limit_task_exp']){
                DB::rollBack();
                throw new OmgException(OmgException::ONEYUAN_FULL_FAIL);//每日限制任务次数 已经达标（该奖品已经参与满）
            }
            //更新任务状态
            $id = $_data->first();
            InviteLimitTask::where(['id'=> $id['id'] ] )->update(['status' => 1]);

            // 根据别名发活动奖品
            $aliasName = 'invite_limit_task_exp';
            $awards = SendAward::ActiveSendAward($this->user_id, $aliasName);

            if(isset($awards[0]['award_name']) && $awards[0]['status']){
                //累加任务完成数
                // $mark = json_encode($awards);//发奖成功备注
                $locked->number += 1;
                $locked->save();
                DB::commit();
                
                return true;
            }else{
                DB::rollBack();
                throw new OmgException(OmgException::SEND_ERROR);//发奖失败
            }

            
        }
        
        
        return false;
    }

    //队列 触发
    public function isTouchTask($alias_name, $tag){
        if(isset($tag['tag']) && $tag['tag'] == 'investment'){
            if($tag['from_user_id'] <= 0 || $tag['is_first'] != 1){
                return false;//投资  没有from_id 或者 不是首投
            }
            //投资触发
            $_user = $tag['from_user_id'];//活动配置中只 配置了邀请人奖励
            //获取奖品详情***********
            $invite_award_info = Activity::where('alias_name', $alias_name)->with('award_invite')->first()->awards->ToArray();
            $invite_money = SendAward::_getAwardInfo($invite_award_info[0]['award_type'],$invite_award_info[0]['award_id']);
            $invited_money = 0;

            $invited_user = $this->user_id;
        }else{
            //绑卡触发
            $url = Config::get('award.reward_http_url');
            $client = new JsonRpcClient($url);
            //获取邀请人id
            $res = $client->getInviteUser(array('uid' => $this->user_id));
            if(!isset($res['result']['data']['id'])){
                return false;//绑卡  没有邀请关系。不发奖
            }
            $_user = $res['result']['data']['id'];

            $invite_award_info = Activity::where('alias_name', $alias_name)->with('awards')->first()->awards->ToArray();
            $invited_award_info = Activity::where('alias_name', $alias_name)->with('award_invite')->first()->awards->ToArray();
            $invite_money = SendAward::_getAwardInfo($invite_award_info[0]['award_type'],$invite_award_info[0]['award_id']);
            $invited_money = SendAward::_getAwardInfo($invited_award_info[0]['award_type'],$invited_award_info[0]['award_id']);
            //获取奖品详情***********
            //邀请了
            $invited_user = $this->user_id;
        }
        
        $_update = InviteLimitTask::where(['user_id'=>$_user , 'date_str'=>$this->whitch_tasks, 'alias_name'=>$alias_name ,'status'=>0])
        ->where('limit_time', '>', date('Y-m-d H:i:s'))
        // ->lockforupdate()
        ->first();
        if(!empty($_update) ){
            //事务开始
            DB::beginTransaction();
            $locked = GlobalAttribute::where(array('key' => $alias_name.$this->whitch_tasks) )
                    ->lockforupdate()->first();
            if(!$locked) {//如果不存在 新建一个 并锁住
                $new_locked_id = GlobalAttribute::create(['key' => $alias_name.$this->whitch_tasks,  'number' => 0]);
                $locked = GlobalAttribute::where(array('id' => $new_locked_id->id) )
                    ->lockforupdate()->first();
                }
                //每日限额
                if($locked->number >= $this->tasks_total[$alias_name]){
                    DB::rollBack();
                    throw new OmgException(OmgException::ONEYUAN_FULL_FAIL);//每日限制任务次数 已经达标（该奖品已经参与满）
                }
                //更新任务状态
                
                InviteLimitTask::where(['id'=> $_update['id'] ] )->update(['status' => 1 ,'user_prize' => $invite_money['money'],'invite_prize'=>$invited_money['money'], 'invite_user_id'=>$invited_user ]);
                //累加任务完成数
                $locked->number += 1;
                $locked->save();
                DB::commit();
                return true;
        }

        return false;


    }


    public function userActivitData(){
        $_data = $this->userTaskDataByDay($this->user_id);
        return $_data;
    }

    //获取 当天增在进行的任务数量
    public function getTaskingByDay(){
        return $_data = InviteLimitTask::select(DB::raw('count(*) as user_count, alias_name'))
        ->where(['date_str'=>$this->whitch_tasks])
        ->where('status',0)
        ->where('limit_time', '>', date('Y-m-d H:i:s'))
        ->groupBy('alias_name')
        ->get();
    }
    //获取 当天已经完成任务数量
    public function getTaskedByDay(){
        $doneTaskArray = [];
        foreach (self::TASK_ID as $key => $value) {
            $_data = GlobalAttributes::getNumber($value.$this->whitch_tasks);
            $doneTaskArray[$value] = $_data;
        }
        return $doneTaskArray;
    }

    public function getAllDoneTaskByUser(){
        if($this->user_id){
            return $_data = InviteLimitTask::where(['user_id'=>$this->user_id , 'status'=>1])->get()->count();
        }
        return 0;
    }


}