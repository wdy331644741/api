<?php
namespace App\Service;

use App\Exceptions\OmgException;
use Lib\Curl;
use OSS\OssClient;
use OSS\Core\OssException;

class AliyunOSSService
{

     public  function uploadFile()
     {
         $accessKeyId = env('OSS_ACCESS_KEY');
         $accessKeySecret = env('OSS_ACCESS_SECRET');
         $endpoint = env('OSS_ENDPOINT');
         try {
             $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
         } catch (OssException $e) {
             print $e->getMessage();
         }


     }


}
