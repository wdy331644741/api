<?php

namespace App\Http\Controllers\bbs;


use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use OSS\OssClient;
use OSS\Core\OssException;
use App\Service\NetEastCheckService;



use Intervention\Image\ImageManager;

use Storage;

use Validator;

class UploadController extends Controller
{
    //

    public function postImg(Request $request)
    {
        
        global $user_id;

        $accessKeyId = "LTAILfIWiFf9WNJI";
        $accessKeySecret = "MU4lPUAVuYSczy2Z8fkmmdLxoWUFOz";
        $endpoint = "oss-cn-qingdao.aliyuncs.com";
        $bucket = "wangli-test";

        $validator = Validator::make($request->all(), [
            'img' => 'required|mimes:png,jpg',
        ]);

        if ($validator->fails()) {
            return $this->outputJson(10001,array('error_msg'=>'图片格式错误'));
        }
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $object = $user_id.time().".".$request->file('img')->getClientOriginalExtension();

            try{
                //上传初始图片
                $res = $ossClient->uploadFile($bucket,$object,$request->file('img')->getRealPath());

                $imgManager = new ImageManager();

                $image = $imgManager->make($res['info']['url']);
                //如果传裁剪坐标
                if($request->width & $request->height & $request->postionX & $request->postionY) {
                    //处理图片
                    $image->crop($request->width, $request->height, $request->postionX, $request->postionY)
                        //保存到本地
                        ->save(dirname(app_path()) . "/storage/images/" . $object);
                    //上传处理掉的图片 覆盖掉之前的原始图片
                    $res = $ossClient->uploadFile($bucket, $object, dirname(app_path()) . "/storage/images/" . $object);
                    //删除掉本地存储的图片
                    Storage::disk("bbsImg")->delete($object);
                }
                $picArrays = [
                    [   "name"=>$res['info']['url'],
                        "type"=>1,
                        "data"=>$res["info"]["url"]
                    ]
                ];
                $inParamImg = array(
                    "images"=>json_encode($picArrays),
                );

                $imgCheck = new NetEastCheckService($inParamImg);
                $checkRes = $imgCheck->imgCheck();
                $maxLevel =-1;
                foreach ($checkRes['result'] as $k=>$v){
                    foreach($v["labels"] as $index=>$label){

                        $maxLevel=$label["level"]>$maxLevel?$label["level"]:$maxLevel;
                    }
                }
                if($maxLevel ==0 ||$maxLevel==1){
                    return [
                        "code"=>0,
                        "success"=>"success",
                        "data"=>[
                            "level"=>$maxLevel,
                            "picUrl"=>$res["info"]["url"],
                        ]
                    ];
                }
                if($maxLevel ==2){
                    return $this->outputJson(10001,array('error_msg'=>'图片保存失败'));
                }
            } catch(OssException $e) {
                return $this->outputJson(10001,array('error_msg'=>'图片保存失败'));
            }

        } catch (OssException $e) {
            return $this->outputJson(10001,array('error_msg'=>'图片保存失败'));
        }


    }
}
