<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityVote;
use Illuminate\Support\Facades\Redis;
use App\Service\ActivityService;
use App\Models\Activity;
use App\Exceptions\OmgException;
use App\Service\SendAward;
use App\Jobs\VoteSendAward;
use PHPMailer\PHPMailer\PHPMailer;
use DB;
use Carbon\Carbon;
use App\Service\InviteTaskService;

//use Illuminate\Foundation\Bus\DispatchesJobs;

class SendMail extends Command
{
    //use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SendMail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '好友邀请3.0 每日发送数据邮件(用于测试环境)';



    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment(PHP_EOL.'发送邮件脚本'.PHP_EOL.'记录日志：logs/vote_cash'.date('Y-m-d').'.log');

        $ser = new InviteTaskService();
        $sgin_date = Carbon::parse($ser->whitch_tasks_start)->modify('-1 day')->toDateTimeString();
        $sgin = date('YmdH' , strtotime($sgin_date) );

        $users = DB::connection('online_data')
            ->table('activity_vote')
            ->select(DB::raw('count(*) as user_count, vote'))
            ->groupBy('status')
            ->get();
        dd($users);
        
        //领取任务人数
        // // $users = DB::connection('online_data')
        $receive = DB::connection('mysql')
            ->table('friend_30_limit_task')
            ->select(DB::raw('count(*) as user_count, alias_name'))
            ->where(['date_str'=> $sgin])
            ->groupBy('alias_name')
            ->get();
        

        //完成任务数
        $done = DB::connection('mysql')
            ->table('friend_30_limit_task')
            ->select(DB::raw('count(*) as user_count, alias_name ,sum(user_prize)+sum(invite_prize) as prize'))
            ->where(['date_str'=> $sgin ,'status'=> 1])
            ->groupBy('alias_name')
            ->get();
        dd($done);

        $mailAddressTest = ['331644741@qq.com'=>'wasd'];
        $mail = new PHPMailer();
        $mail->SMTPDebug = 2;           // 开启Debug
        $mail->IsSMTP();                // 使用SMTP模式发送新建
        $mail->Host = "smtp.exmail.qq.com"; // QQ企业邮箱SMTP服务器地址
        $mail->Port = 465;  //邮件发送端口
        $mail->SMTPAuth = true;         // 打开SMTP认证，本地搭建的也许不会需要这个参数
        $mail->SMTPSecure = "ssl";      // 打开SSL加密，这里是为了解决QQ企业邮箱的加密认证问题的~~
        $mail->Username = "wangdongyang@wanglibank.com";   // SMTP用户名  注意：普通邮件认证不需要加 @域名，我这里是QQ企业邮箱必须使用全部用户名
        $mail->Password = "4aA66HP9isMgxozK";        // SMTP 密码
        $mail->From = "wangdongyang@wanglibank.com";      // 发件人邮箱
        $mail->FromName =  "王东洋";  // 发件人
        $mail->Subject = '赚呗核心指标日报';
        $mail->CharSet = "UTF-8";            // 这里指定字符集！
        $mail->Encoding = "base64";
        //$mail->AddAttachment($file,'赚呗核心指标日报.xlsx'); // 添加附件,并指定名称
        $mail->IsHTML(true); //支持html格式内容
        // $mail->Body = $template;
        $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
        $mail->AltBody ="text/html";
//return $mail;
        foreach($mailAddressTest as $k=>$val){
                $mail->AddAddress($k,$val);
        }

        if(!$mail->send()){
            $error=$mail->ErrorInfo;
            return $error;
        }else{
            $headers = ['领任务人数', '完成任务人数'];
            $table1 = ['领取任务1人数', '完成任务1人数', '任务1奖励金额'];
            $table2 = ['领取任务2人数', '完成任务2人数', '任务2奖励金额'];
            $table3 = ['领取任务3人数', '完成任务3人数', '任务3奖励金额'];


            $users = $arrayName = array(['Name'=>'shiwenyuan','Email'=>'shiwenyuan@111']);

            $this->table($headers, $users);
            return true;
        }
        

    }


}
