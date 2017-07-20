<?php
namespace App\Service;

use App\Exceptions\OmgException;
use Lib\Curl;
use OSS\OssClient;
use OSS\Core\OssException;

class AliyunOSSService
{
    static  private $accessKeyId;
    static  private $accessKeySecret;
    static  private $endPoint;
    static private $bucket;
    public function __construct()
    {
        self::$accessKeyId = env('OSS_ACCESS_KEY1');
        self::$accessKeySecret = env('OSS_ACCESS_SECRET1');
        self::$endPoint = env('OSS_ENDPOINT1');
        self::$bucket = env('OSS_BUCKET1');

    }
    /*
     *
     * 上传图片
     * */
     public  function uploadFile($object,$path)
     {
         try {
             $ossClient = new OssClient(self::$accessKeyId, self::$accessKeySecret, self::$endPoint);
             return  $ossClient->uploadFile(self::$bucket,$object,$path);
         } catch (OssException $e) {
             throw new \Exception($e->getMessage(),502);
         }


     }
     /*
      *
      * 删除信息
      * */
     public function delFile($object,$path)
     {
         try {
             $ossClient = new OssClient(self::$accessKeyId, self::$accessKeySecret, self::$endPoint);
             return  $ossClient->deleteObject(self::$bucket,$object,$path);
         } catch (OssException $e) {
            throw new \Exception($e->getMessage(),404);
}

     }


}
