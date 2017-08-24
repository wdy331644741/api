<?php

namespace App\Http\Controllers\bbs;


use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use OSS\OssClient;
use OSS\Core\OssException;
use App\Service\NetEastCheckService;
use App\Service\AliyunOSSService;



use Intervention\Image\ImageManager;

use Storage;

use Validator;

class UploadController extends Controller
{
    //

    public function postImg(Request $request)
    {

        global $user_id;

        $messages = [
            'mimes'    => '图片格式错误',
            'max'    => '图片太大',
        ];
        $validator = Validator::make($request->all(), [
            'img' => 'required|mimes:png,jpg,jpeg|max:5120',
        ],$messages);

        if ($validator->fails()) {
            return $this->outputJson([
                "jsonrpc" => 2.0,
                "error" => [
                    "code" => -3402,
                    "message" => $validator->messages('img')->first(),
                ],
                "id" => 1
            ]);
        }
        try{
            //上传初始图片
            $aliyunOssClient = new AliyunOSSService();
            $object = $user_id.time().rand(0,25).".".$request->file('img')->getClientOriginalExtension();
            $res = $aliyunOssClient->uploadFile($object,$request->file('img')->getRealPath());

            $imgManager = new ImageManager();

            $image = $imgManager->make($res['info']['url']);
            //如果传裁剪坐标
            if($request->width & $request->height & $request->postionX & $request->postionY) {
                //处理图片
                $image->crop($request->width, $request->height, $request->postionX, $request->postionY)
                    //保存到本地
                    ->save(dirname(app_path()) . "/storage/images/" . $object);
                //上传处理掉的图片 覆盖掉之前的原始图片
                $res = $aliyunOssClient->uploadFile($object, dirname(app_path()) . "/storage/images/" . $object);
                //$res = $aliyunOssclient->uploadFile($bucket, $object, dirname(app_path()) . "/storage/images/" . $object);
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
            if($checkRes['code'] == 500){
                //访问网易服务出现问题
                return $this->outputJson([
                    "jsonrpc" => 2.0,
                    "error" => [
                        "code" => -3402,
                        "message" => "图片保存失败"
                    ],
                    "id" => 1
                ]);
            }
            if($checkRes['code'] == 200) {
                $maxLevel = -1;
                foreach ($checkRes['result'] as $k => $v) {
                    foreach ($v["labels"] as $index => $label) {

                        $maxLevel = $label["level"] > $maxLevel ? $label["level"] : $maxLevel;
                    }
                }
                if ($maxLevel == 0 || $maxLevel == 1) {
                    return [
                        "code" => 0,
                        "success" => "success",
                        "data" => [
                            "level" => $maxLevel,
                            "picUrl" => $res["info"]["url"],
                        ]
                    ];
                }
                if ($maxLevel == 2) {
                    return $this->outputJson([
                        "jsonrpc" => 2.0,
                        "error" => [
                            "code" => -3402,
                            "message" => "图片审核未通过"
                        ],
                        "id" => 1
                    ]);
                }
            }else{
                return [
                    "code" => 0,
                    "success" => "success",
                    "data" => [
                        "level" => 500,
                        "picUrl" => $res["info"]["url"],
                    ]
                ];
            }
        } catch(OssException $e) {
            return $this->outputJson([
                "jsonrpc" => 2.0,
                "error" => [
                    "code" => -3402,
                    "message" => "图片保存失败"
                ],
                "id" => 1
            ]);
        }

    }
}
