<?php

namespace App\Http\Controllers\bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use OSS\OssClient;
use OSS\Core\OssException;

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
                dd($res);
            } catch(OssException $e) {
                printf(__FUNCTION__ . ": FAILED\n");
                printf($e->getMessage() . "\n");
                return;
            }
            print(__FUNCTION__ . ": OK" . "\n");
        } catch (OssException $e) {
            print $e->getMessage();
        }


    }
}
