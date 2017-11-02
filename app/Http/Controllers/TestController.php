<?php

namespace App\Http\Controllers;

use App\Models\SendRewardLog;
use App\Models\UserAttribute;
use App\Service\Scratch;
use App\Service\SendAward;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Cms\Content;
use App\Models\Cms\ContentType;
use  \GuzzleHttp\Client;
use Lib\Weixin;
use Lib\JsonRpcClient;
use Config;
use DB;
use App\Models\Cqssc;
use App\Service\NvshenyueService;
use App\Service\TzyxjService;
use App\Service\PoBaiYiService;

use Excel;


class TestController extends Controller
{
    public function getCustomExperience(){
        return view('custom_experience');
    }
    public function postCustomExperience(Request $request){
        $this->param = [];
        $this->param['sourceId'] = intval($request->source_id);
        $this->param['sourceName'] = $request->source_name;
        $this->param['multiple'] = intval($request->multiple);
        $this->param['day'] = intval($request->day);
        if(empty($this->param['sourceId']) || empty($this->param['sourceName']) || empty($this->param['multiple']) || empty($this->param['day'])){
            return 'params_error';
        }
        if(!empty($this->param['sourceId']) && $this->param['sourceId'] < 50000000){
            return '活动ID 必须大于等于 50000000';
        }
        if(!empty($this->param['sourceName']) && strlen($this->param['sourceName']) < 2){
            return '活动名 必须大于等于 两个字符';
        }
        if ($request->hasFile('xls_file')) {
            //验证文件上传中是否出错
            if ($request->file('xls_file')->isValid()) {
                $mimeTye = $request->file('xls_file')->getClientOriginalExtension();
                $types = array('xls', 'xlsx');
                if (in_array($mimeTye, $types)) {
                    $file = $request->file('xls_file');
                    Excel::load($file,function($reader) {
                        $reader = $reader->getSheet(0);
                        $data = $reader->toArray();
                        $res = $this->_sendExperience($data,$this->param);
                        echo "<pre>";
                        print_r($res);exit;
                    });


                }
            }
        }
        return 'file_not_empty';
    }
    private function _sendExperience($data,$param){
        set_time_limit(0);
        $err = ['err'=>[],'is_exist'=>[],'msg'=>[]];
        if(empty($data)){
            return $err;
        }
        foreach($data as $key => $item){
            if($item[0] <= 0 || $item[1] <= 0){
                $err['err'][$key] = 'key:'.$key.'_err';
                continue;
            }
            $money = $item[1] * $param['multiple'];
            if($money <= 0){
                $err['err'][$key] = 'key:'.$key.'_money_err';
                continue;
            }
            //判断是否领取
            $count = SendRewardLog::where(['user_id'=>$item[0],'activity_id'=>$param['sourceId']])->count();
            if($count >= 1){
                $err['is_exist'][$key] = 'key:'.$key.'_is_exist';
                continue;
            }
            $awards['id'] = 0;
            $awards['user_id'] = $item[0];
            $awards['source_id'] = $param['sourceId'];
            $awards['name'] = $money.'体验金';
            $awards['source_name'] = $param['sourceName'];
            $awards['experience_amount_money'] = $money;
            $awards['effective_time_type'] = 1;
            $awards['effective_time_day'] = $param['day'];
            $awards['platform_type'] = 0;
            $awards['limit_desc'] = '';
            $awards['trigger'] = '';
            $awards['mail'] = "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。";
            $return = SendAward::experience($awards);
            if(isset($return['status']) && $return['status'] == true){
                $err['msg'][$key] = 'key:'.$key.'true';
            }else{
                $err['err'][$key] = 'key:'.$key.'_send_err';
            }
            usleep(60000);
        }
        return $err;
    }
    public function getScratchReissue(Request $request){
        $status = $request->status;
        //从接口获取9月1日的投资记录
        $url = "https://www.wanglibao.com/pro/api.php";
        $client = new JsonRpcClient($url);
        $investData = $client->guagualeAct();
        $investData = isset($investData['result']) ? $investData['result'] : [];
        if($status == 1){
            echo count($investData);
            print_r($investData);
            exit;
        }
        //已经补发过的用户id
        $continueID = [
            '2437876',
            '1703223',
            '134996',
            '752794',
            1904585,
            5329,
            2388632,
            259405,
            1310220,
            107217,
            763751,
            1425118,
            1425118,
            1874409,
            1824535,
            2376582,
            2362920,
            274100,
            1443646,
            554137,
        ];
        echo "总条数：".count($investData)."条<br />";
        $continueCount = 0;
        $count = 0;
        if(!empty($investData)){
            foreach ($investData as $item){
                if(in_array($item['user_id'],$continueID)){
                    $continueCount++;
                    continue;
                }
                //补发次数
                $params = [];
                $params['user_id'] = $item['user_id'];//用户id
                $params['scatter_type'] = 2;
                $params['period'] = $item['period'];//标期
                $params['Investment_amount'] = $item['total_amount'];//投资金额
                //执行加次数
                Scratch::addScratchNum($params);
                $count++;
            }
        }
        echo "之前已补发：".$continueCount."条<br />";
        echo "接口补发：".$count."条<br />";
    }
    public function getSigninRepair(Request $request){
        //停服当天时间
        $closeTime = $request->closeTime;
        $dateType = date("H:i:s",strtotime($closeTime));
        $openTime = $request->signinTime;
        if(empty($closeTime) || $dateType != '00:00:00' || empty(strtotime($openTime))){
            return '时间参数格式不正确';
        }
        //获取连续签到到停服当天的数据
        $upData = [];
        $upData['updated_at'] = date("Y-m-d H:i:s",strtotime($openTime));
        $res = UserAttribute::where('updated_at','like',$closeTime.'%')->where('key','signin')->update($upData);
        echo "修改了".$res."条";
    }
    public function getPobaiyi($userId, $money) {
        PoBaiYiService::addMoney($userId, $money);
    }
    public function getPobaiyi2($userId, $amount, $type, $period) {
        $investment = [
            'user_id' => $userId,
            'Investment_amount' => $amount,
            'scatter_type' => $type,
            'period' => $period,
        ];
        PoBaiYiService::addMoneyByInvestment($investment);
    }

    public function getTzyxj($userId, $amount) {
        if(empty($userId) || empty($amount)) {
            return 'invalid params';
        }
        return TzyxjService::addRecord($userId, $amount);
    }
    public function getTest2($userId, $number) {
        $result = NvshenyueService::addChanceByInvest($userId, $number);
        var_dump($result);
    }

    /*
    public function getNvshenyue() {
        $arr = ['nv' => 10, 'shen' => 5, 'yue' => 40, 'kuai' => 10, 'le' => 35];
        $num = 100;
        $total = array_sum($arr);

        while($num > 0) {
            $targetWord = '';
            $rand = rand(1, $total);
            foreach($arr as $key => $value) {
                $rand -= $value;
                if($rand <= 0) {
                    $targetWord = $key;
                    break;
                }
            }
            if($num <= 5) {
                $randNum = $num;
            }else{
                $randNum = rand(5, $num);
            }

            if(isset($result[$key])){
                $result[$targetWord] += $randNum;
            }else{
                $result[$targetWord] = $randNum;
            }
            $num -= $randNum;
        }

        var_dump($result);

    }
    public function getNsy($num) {
        $arr = ['女' => 20, '神' => 25, '月' => 15, '快' => 20, '乐' => 20];
        $rangeMultiple = 1000;
        $rangeMin = 0.6 * $rangeMultiple;
        $rangeMax = 1.4 * $rangeMultiple;
        $total = array_sum($arr);
        //$num = 100;
        $result = [];
        // 粗略分配
        foreach($arr as $key => $value) {
            $range = rand($rangeMin, $rangeMax)/$rangeMultiple;
            $prop = $num*$range*($value/$total);
            if(intval(($prop*1000))%1000 > rand(1, 1000)) {
                $result[$key] = intval($prop)+1;
            }else{
                $result[$key] = intval($prop);
            }
        }
        //补余
        $nowTotal = array_sum($result);
        $diffValue = $num - $nowTotal;
        $i = 0;
        while($diffValue !== 0) {
            $i++;
            $rand = rand(1, $total);
            foreach($arr as $key => $value) {
                $rand -= $value;
                if($rand <= 0) {
                    break;
                }
            }
            $result[$key] += $diffValue;
            if($result[$key] < 0) {
                $diffValue = $result[$key];
                $result[$key] = 0;
            }else{
                $diffValue = 0;
            }
        }
        return $result;

    }

    public function getTest() {
        $totalResult = [];
        $total = 0;
        for($i = 1; $i <= 100; $i ++) {
            for($j = 0; $j < 10; $j++) {
                $total += $i;
                $result = $this->getNsy($i);
                foreach($result as $key => $value) {
                    echo  '' . $value . '_' ;
                   if(isset($totalResult[$key])) {
                       $totalResult[$key] += $value;
                   }else{
                       $totalResult[$key] = $value;
                   }
                }
                echo '总:' . array_sum($result);
                echo '<hr />';
            }
        }

        $sum = array_sum($totalResult);
        foreach($totalResult as $key => $value) {
           echo $key . ':' . $value . ' ';
           echo intval(10000*($value/$sum))/100;
           echo ' ';
        }
        echo '<hr />';
        echo $total;
        echo '|';
        echo $sum;
    }
    */


    public function getXjdbTotal() {
        $total = 0;
        $config = Config::get('activity.xjdb');
        foreach($config as $position) {
            foreach($position['items'] as $item) {
                foreach($item['awards'] as $award) {
                    $total  = bcadd($total, bcmul($award['money'], $award['num']));
                }
            }
        }
        return $total;
    }
    public function getCqssc(){
        for($i = 3600*24; $i <= 3600*48; $i+=10) {
            $openTiemStamp = $this->getOpenTimeStamp($i);
            $expect = $this->getOpenExpect($i);
            echo date('Y-m-d H:i:s', $i) . ' ' . date('Y-m-d H:i:s', $openTiemStamp) . '| ' . $expect . PHP_EOL . '<br />';
        }
        return;
        $res = Cqssc::where('opentime', '>=',$date )->orderBy('expect', 'asc')->first();
    }

    public function getUserInfo() {
        $userId = 1231312312;
        $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
        $userBase = $client->userBasicInfo(array('userId'=>$userId));

        if(!$userBase || !isset($userBase['result']['data']['trade_pwd']) || empty($userBase['result']['data']['trade_pwd']) ) {
            $awardStatus= false;
        }
        var_dump($awardStatus);
        dd($userBase);
    }

    // 获取开奖时间戳
    public function getOpenTimeStamp($timestamp) {
        $dayTimeStamp = ($timestamp+8*3600 - 40)%(3600*24);
        if($dayTimeStamp <= 6900 || $dayTimeStamp > 79200) { // 时间 <= 1:55:40 || 时间 > 22:00:40
            $remainder = $dayTimeStamp%300;
            $seconds = $remainder == 0 ? 0 : 300-$remainder;
            $openTimeStamp = $timestamp + $seconds;
        } else if($dayTimeStamp > 6900 && $dayTimeStamp < 36000) { // 时间 > 1:55:40 && 时间 < 10:00:40
            $openTimeStamp = strtotime(date('Y-m-d 10:00:40', $timestamp));
        } else if($dayTimeStamp >= 36000 && $dayTimeStamp <= 79200) { // 时间 >= 10:00:40 && 时间  <= 22:00:40
            $remainder = $dayTimeStamp%600;
            $seconds = $remainder == 0 ? 0 : 600-$remainder;
            $openTimeStamp = $timestamp + $seconds;
        }
        return $openTimeStamp;
    }

    //获取开奖期数
    public function  getOpenExpect($timestamp) {
        $dayTimeStamp = ($timestamp+8*3600 - 40)%(3600*24);
        if($dayTimeStamp <= 6900) { // 时间 <= 1:55:40
            $remainder = $dayTimeStamp%300;
            $seconds = $remainder == 0 ? 0 : 300-$remainder;
            $expect = ($dayTimeStamp + $seconds)/300;
        } else if($dayTimeStamp > 6900 && $dayTimeStamp <= 36000) { // 时间 > 1:55:40 && 时间 < 10:00:40
            $expect = 24;
        } else if($dayTimeStamp > 36000 && $dayTimeStamp <= 79200) { // 时间 >= 10:00:40 && 时间  <= 22:00:40
            $remainder = $dayTimeStamp%600;
            $seconds = $remainder == 0 ? 0 : 600-$remainder;
            $expect = 24+($dayTimeStamp + $seconds - 36000)/600;
        } else if($dayTimeStamp > 79200) { //时间 > 22:00:40
            $remainder = $dayTimeStamp%300;
            $seconds = $remainder == 0 ? 0 : 300-$remainder;
            $expect = 96+($dayTimeStamp + $seconds - 79200)/300;
        }
        $expect = $expect == 0 ? 120 : $expect;
        return date('Ymd', $timestamp-41) . str_pad($expect, 3, '0', STR_PAD_LEFT);
    }
    public function getIndex(){
        $pub_key = file_get_contents(config_path('key/xy_public_key.pem'));
        $res = openssl_pkey_get_public($pub_key);
        dd($res);
        $data = array(
            'first'=>array(
                'value'=>"test",
                'color'=>'#000000'
            ),
            'keyword1'=>array(
                'value'=>date('Y-m-d H:i'),
                'color'=>'#000000'
            ),
            'keyword2'=>array(
                'value'=>123,
                'color'=>'#00000'
            ),
            'remark'=>array(
                'value'=>'分享活动拿更多奖励，速戳详情领取！',
                'color'=>'#173177'
            )
        );
        $wxObj = new Weixin();
        $status = $wxObj->send_template_msg("ovewut6VpqDz6ux4nJg2cKx0srh0",Config::get('open.weixin.msg_template.sign_daily'),$data,"http://www.baidu.com");
    }

    //模拟爱有钱请求
    public function getAyq(){
        $time = time();
        $data = array(
            'bind_uid'=>5100195,
            'service'=>'get_userinfo',
            'time'=>$time,
            'cid'=>303250
        );
        $sign = md5($this->createSignStr($data));
        $data['sign'] = $sign;
        $client = new Client([
            'base_uri'=>"https://php1.wanglibao.com",
            'timeout'=>9999.0
        ]);
        $signStr = md5($this->createSignStr($data));
        $data['sign'] = $signStr;
        $res = $client->post('/yunying/open/ayq-register',['form_params'=>$data]);
        dd(json_decode($res->getBody()));
    }



    private function createSignStr($data){
        if(!is_array($data)){
            return '';
        }
        ksort($data);
        $sign_str='';
        foreach($data as $key=>$val){
            if(isset($val) && !is_null($val) && @$val!=''){
                if($key == "realname"){
                    $sign_str.='&'.$key.'='.trim($val);
                }else{
                    $sign_str.='&'.$key.'='.trim($val);
                }
            }
        }
        if ($sign_str!='') {
            $sign_str = substr ( $sign_str, 1 );
        }
        return $sign_str."4b701c4aca7dd5ee6ddc78c9e0b741df";
    }
    //导入媒体报道数据
    public function getMtJoin(){
        $contentTypeId = ContentType::where('alias_name','report')->value('id');
        $data = DB::connection('python')->select('select name,link,created_at,image,keywords,description,content from marketing_newsandreport ORDER BY created_at ASC ');
        $new_data = array();
        foreach ($data as $item){
            $new_data[] = array(
                'type_id'=>$contentTypeId,
                'cover'=>'/media/'.$item->image,
                'title'=>$item->name,
                'release'=>1,
                'release_at'=>$item->created_at,
                'created_at'=>$item->created_at,
                'description'=>$item->description,
                'keywords'=>$item->keywords,
                'content'=>$item->content
            );
        }
        DB::beginTransaction();
        $res = DB::table('cms_contents')->insert($new_data);
        if($res){
            DB::commit();
            return $this->outputJson(0);
        }else{
            DB::rollBack();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }

    //导入网利动态数据
    public function getWldtJoin(){
        $contentTypeId = ContentType::where('alias_name','trends')->value('id');
        $data = DB::connection('python')->select('select title,created_at,description,content from wanglibao_banner_aboutdynamic order by created_at ASC');
        $new_data = array();
        foreach ($data as $item){
            $new_data[] = array(
                'type_id'=>$contentTypeId,
                'title'=>$item->title,
                'release'=>1,
                'release_at'=>$item->created_at,
                'created_at'=>$item->created_at,
                'description'=>$item->description,
                'content'=>$item->content
            );
        }
        DB::beginTransaction();
        $res = DB::table('cms_contents')->insert($new_data);
        if($res){
            DB::commit();
            return $this->outputJson(0);
        }else{
            DB::rollBack();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //导入网站公告数据
    public function getNoticeJoin(){
        $data = DB::connection('python')->select('select device,title,createtime,content from wanglibao_announcement_announcement order by createtime ASC');
        $new_data = array();
        foreach ($data as $item){
            $new_item = array(
                'title'=>$item->title,
                'release'=>1,
                'release_at'=>$item->createtime,
                'created_at'=>$item->createtime,
                'content'=>$item->content
            );
            if($item->device == "pc&app"){
                $new_item['platform'] = 0;
            }elseif($item->device == "pc"){
                $new_item['platform'] = 1;
            }else{
                $new_item['platform'] = 2;
            }
            $new_data[] = $new_item;
        }
        DB::beginTransaction();
        for ($i=0; $i<4;$i++){
            $offset = $i*200;
            $insdata = array_slice($new_data,$offset,200);
            $res = DB::table('cms_notices')->insert($insdata);
        }
        if($res){
            DB::commit();
            return $this->outputJson(0);
        }else{
            DB::rollBack();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }

    //导入渠道数据
    public function getChannelJoin(){
        $data = DB::connection('python')->select('select code,description,created_at,coop_status,classification,is_abandoned from marketing_channels order by created_at ASC');
        $new_data = array();
        foreach ($data as $item){
            $new_item = array(
                'name'=>$item->description,
                'alias_name'=>$item->code,
                'coop_status'=>$item->coop_status,
                'created_at'=>$item->created_at,
                'classification'=>$item->classification,
                'is_abandoned'=>$item->is_abandoned
            );
            $insdata[] = $new_item;
        }
        DB::beginTransaction();
        $res = DB::table('channels')->insert($insdata);
        if($res){
            DB::commit();
            return $this->outputJson(0);
        }else{
            DB::rollBack();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

}
