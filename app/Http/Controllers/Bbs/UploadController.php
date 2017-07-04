<?php

namespace App\Http\Controllers\bbs;

use App\Exceptions\OmgException;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use OSS\OssClient;
use OSS\Core\OssException;
use App\Service\NetEastCheckService;

class UploadController extends Controller
{
    //

    public function postImg()
    {
        global $user_id;
        $user_id=123;
        $accessKeyId = "LTAILfIWiFf9WNJI";
        $accessKeySecret = "MU4lPUAVuYSczy2Z8fkmmdLxoWUFOz";
        $endpoint = "oss-cn-qingdao.aliyuncs.com";
        $bucket = "wangli-test";
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $object = $user_id."/".$user_id.time().".".pathinfo($_FILES['file']['name'])['extension'];

            try{
                $res  = $ossClient->uploadFile($bucket, $object, $_FILES['file']['tmp_name']);
                $picArrays = [
                    [   "name"=>$res['info']['url'],
                        "type"=>1,
                        "data"=>$res["info"]["url"]
                    ]
                ];
                $inParamImg = array(
                    "images"=>json_encode($picArrays),
                );
                //dd($picArrays);
                $imgCheck = new NetEastCheckService($inParamImg);
                $checkRes = $imgCheck->imgCheck();
                $maxLevel =-1;
                foreach ($checkRes['result'] as $k=>$v){
                    foreach($v["labels"] as $index=>$label){
                        echo "label:{$label["label"]}, level={$label["level"]}, rate={$label["rate"]}\n";
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
                    throw  new OmgException(OmgException::UPLOAD_IMG_ERROR);
                }
            } catch(OssException $e) {
                throw new OmgException(OmgException::UPLOAD_IMG_ERROR);
            }

        } catch (OssException $e) {
            throw new OmgException(OmgException::UPLOAD_IMG_ERROR);
        }


    }
}
