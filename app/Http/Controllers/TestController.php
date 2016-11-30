<?php

namespace App\Http\Controllers;

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


class TestController extends Controller
{
    public function getCqssc(){
        for($i = 0; $i <= 3600*24; $i+=10) {
            $openTiemStamp = $this->getOpenTimeStamp($i);    
            echo date('Y-m-d H:i:s', $i) . ' ' . date('Y-m-d H:i:s', $openTiemStamp) . PHP_EOL . '<br />';
        }
        return;
        $res = Cqssc::where('opentime', '>=',$date )->orderBy('expect', 'asc')->first();
    }
    
    // 获取开奖时间戳
    public function getOpenTimeStamp($timestamp) {
        $date = date('Y-m-d H:i:s', $timestamp);
        $dayTimeStamp = ($timestamp+8*3600)%(3600*24);
        if($dayTimeStamp <= 6940 || $dayTimeStamp > 79240) { // 时间 <= 1:55:40 || 时间 > 22:00:40
            $remainder = ($dayTimeStamp-40)%300;
            $seconds = $remainder == 0 ? 0 : 300-$remainder;
            $openTimeStamp = $timestamp + $seconds ;
        } else if($dayTimeStamp > 6940 && $dayTimeStamp < 36040) { // 时间 > 1:55:40 && 时间 < 10:00:40
            $openTimeStamp = strtotime(date('Y-m-d 10:00:40', $timestamp));
        } else if($dayTimeStamp >= 36040 && $dayTimeStamp <= 79240) { // 时间 >= 10:00:40 && 时间  <= 22:00:40
            $remainder = ($dayTimeStamp-40)%600;
            $seconds = $remainder == 0 ? 0 : 600-$remainder;
            $openTimeStamp = $timestamp + $seconds ;
        }
        return $openTimeStamp;
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
