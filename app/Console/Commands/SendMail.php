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

        // $users = DB::connection('online_data')
        //     ->table('activity_vote')
        //     ->select(DB::raw('count(*) as user_count, vote'))
        //     ->groupBy('status')
        //     ->get();
        // dd($users);

        //领取任务人数
        $receive = DB::connection('online_data')
        // $receive = DB::connection('mysql')
            ->table('friend_30_limit_task')
            ->select(DB::raw('count(*) as user_count, alias_name'))
            ->where(['date_str'=> $sgin])
            ->groupBy('alias_name')
            ->get();
        //https://github.com/php/php-src/blob/PHP-7.0.0/UPGRADING#L629
        $receive = json_decode(json_encode($receive), true);//测试环境php5版本 不支持对象数组
        $receive = array_column($receive, 'user_count','alias_name');


        //完成任务数
        $done = DB::connection('online_data')
        // $done = DB::connection('mysql')
            ->table('friend_30_limit_task')
            ->select(DB::raw('count(*) as user_count, alias_name ,sum(user_prize)+sum(invite_prize) as prize'))
            ->where(['date_str'=> $sgin ,'status'=> 1])
            ->groupBy('alias_name')
            ->get();
        //https://github.com/php/php-src/blob/PHP-7.0.0/UPGRADING#L629
        $done = json_decode(json_encode($done), true);//测试环境php5版本 不支持对象数组
        $done_count = array_column($done, 'user_count','alias_name');
        $done_prize = array_column($done, 'prize','alias_name');


        $headers = ['任务名','领任务人数', '完成任务人数','任务奖励'];


        // $headers_data = array(['a'=>array_sum($receive),'b'=>array_sum($done_count)]);
        // $this->table($headers, $headers_data);


        $_data = [];
        $table_tr = '';
        foreach ($ser::TASK_ID as $key => $value) {
            switch ($value) {
                case 'invite_limit_task_exp':
                    $name = '任务1（分享体验金）';
                    break;
                case 'invite_limit_task_bind':
                    $name = '任务2（绑卡）';
                    break;
                case 'invite_limit_task_invest':
                    $name = '任务3（邀请首投）';
                    break;

                default:
                    $name = 'fuck';
                    break;
            }
            $table_tr .= <<<HTML
<tr><td>$name</td>
<td>$receive[$value]</td>
<td>$done_count[$value]</td>
<td>$done_prize[$value]</td></tr>
HTML;
            $alisa = [
                'a' => $value,
                'b' => $receive[$value] , 
                'c'=> $done_count[$value] ,
                'd'=> $done_prize[$value] 
            ];
            array_push($_data, $alisa);
        }



        $mail_table = <<<HTML
<html>
<head>日期（批次）：$sgin</head>
<body>
<table border="1" style="border-collapse:collapse">
<tr>
<td>任务名</td>
<td>领任务人数</td>
<td>完成任务人数</td>
<td>任务奖励</td>
</tr>
.$table_tr.
</table>

</body></html>
HTML;



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
        $mail->Subject = '邀请好友3.0数据需求';
        $mail->CharSet = "UTF-8";            // 这里指定字符集！
        $mail->Encoding = "base64";
        //$mail->AddAttachment($file,'赚呗核心指标日报.xlsx'); // 添加附件,并指定名称
        $mail->IsHTML(true); //支持html格式内容
        $mail->Body = $mail_table;
        $mail->AltBody ="text/html";

        foreach($mailAddressTest as $k=>$val){
                $mail->AddAddress($k,$val);
        }

        if(!$mail->send()){
            $error=$mail->ErrorInfo;
            return $error;
        }else{
            $this->table($headers, $_data);
            return true;
        }
        

    }


}
