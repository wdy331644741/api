<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use OSS\OssClient;
use OSS\Core\OssException;

class MediaController extends Controller
{
    public function getIndex(Request $request){
        $accessKeyId = env('OSS_ACCESS_KEY');
        $accessKeySecret = env('OSS_ACCESS_SECRET');
        $endpoint = env('OSS_ENDPOINT');
        $bucket = env('OSS_BUCKET');
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $path = $request->path;
        try{
            $content = $ossClient->getObject($bucket, $path);
        } catch(OssException $e) {
            abort(404); 
        }
        return response($content)->withHeaders([
            'Content-type' => getMimeTypeByExtension($path),
            'Cache-Control'=> "max-age=" . 60*60*24*30,
            'Expires' => gmdate('r', time()+60*60*24*30),
        ]);
    }

}
