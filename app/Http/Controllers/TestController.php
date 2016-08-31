<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Cms\Content;
use App\Models\Cms\ContentType;
use App\Models\Cms\Notice;
use Lib\Weixin;

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


    //导入媒体报道数据
    public function getMtJoin(){
        $contentTypeId = ContentType::where('alias_name','report')->value('id');
        $data = DB::connection('python')->select('select name,link,created_at,image,keywords,description,content from marketing_newsandreport ORDER BY created_at DESC ');
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
        $data = DB::connection('python')->select('select title,created_at,description,content from wanglibao_banner_aboutdynamic order by priority desc');
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
        $data = DB::connection('python')->select('select device,title,createtime,content from wanglibao_announcement_announcement order by priority desc');
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

}
