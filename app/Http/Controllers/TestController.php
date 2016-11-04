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


class TestController extends Controller
{
    public function getIndex(){
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
    public function getAyqPost(){
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountRegister(array('channel'=>'test','phone'=>'10000000023'));
        $client = new Client([
            'base_uri'=>"https://php1.wanglibao.com",
            'timeout'=>9999.0
        ]);
        $time = time();
        $data = [
            'mobile'=>'15831458983',
            'realname'=>'赵东冉',
            'uid'=>123000,
            'cardno'=>'13082119911208257X',
            'service'=>'register_bind',
            'time'=>$time,
            'cid'=>303250,
        ];
        $signStr = md5($this->createSignStr($data));
        $data['sign'] = $signStr;
        $res = $client->post('/yunying/open/ayq-register',['form_params'=>$data]);
        dd(json_decode($res->getBody()));
    }


    function createSignStr($data){
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
        //file_put_contents(storage_path('logs/signstr-'.date('Y-m-d')).'.log',date('Y-m-d').'   sign：'.$sign_str.'-4b701c4aca7dd5ee6ddc78c9e0b741df'.PHP_EOL,FILE_APPEND);
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
