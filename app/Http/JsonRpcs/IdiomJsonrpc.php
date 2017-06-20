<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\Cms\Idiom;
use App\Models\Cms\Welcome;

class IdiomJsonrpc extends JsonRpc {
    
    /**
     *  获取当前时间的语句
     *
     * @JsonRpcMethod
     */
    public function getIdiomList() {
        $date = date('Y-m-d H:i:s');
        $res = Idiom::where('start_at','<',$date)->where('end_at','>',$date)->orderBy('priority','ASC')->first();
        if($res){
            $data = explode(';',$res->contents);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data,
            );
        }else{
            throw new OmgException(OmgException::NO_DATA);
        }
    }


    /**
     *  启动界面欢迎语
     *
     * @JsonRpcMethod
     */
    public function getWelcomes() {
        $data = Welcome::where('enable',1)->first();
        if($data){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data->toArray(),
            );
        }else{
            throw new OmgException(OmgException::NO_DATA);
        }
    }
}
