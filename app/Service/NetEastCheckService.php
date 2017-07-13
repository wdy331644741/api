<?php
namespace App\Service;

use App\Exceptions\OmgException;
use function GuzzleHttp\Promise\queue;
use Lib\Curl;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class NetEastCheckService
{

    const VERSION = 'v3';
    const API_TIMEOUT=2;
    private $params = [];
    private $inParams=[];
    public function __construct($param)
    {
        $this->inParams = $param;

    }
    /*
     * param  dataId        数据唯一标识   Y
     * param  content       用户发表内容   Y
     * param  dataType      子数据类型     N
     * param   ip           用户IP地址     N
     * param  account       用户唯一标识   N
     * param  deviceType    用户设备类型   N
     * param  deviceId      用户设备 id    N
     * param  callback      数据回调参数   N
     * param  publishTime   发表时间       N
     *
     * */
    public  function textCheck()
    {
        $this->params["secretId"] = env('NETEASE_KEY');
        $this->params["businessId"] = env('TEXT_BUSINESSID');
        $this->params["version"] = self::VERSION;
        $this->params["timestamp"] = sprintf("%d", round(microtime(true)*1000));// time in milliseconds
        $this->params["nonce"] = sprintf("%d", rand()); // random int
        $this->params['dataId'] = $this->inParams['dataId'];//设置为时间戳
        $this->params['content'] = $this->inParams['content'];
        $this->params['dataType'] = isset($this->inParams['dataType'])?$this->inParams['dataType']:"";
        $this->params['ip'] = isset($this->inParams['ip'])?$this->inParams['ip']:"";
        $this->params['account'] = isset($this->inParams['account'])?$this->inParams['account']:"";
        $this->params['deviceType'] = isset($this->inParams['deviceType'])?$this->inParams['deviceType']:"";
        $this->params['deviceId'] = isset($this->inParams['deviceId'])?$this->inParams['deviceId']:"";
        $this->params['callback'] = isset($this->inParams['callback'])?$this->inParams['callback']:"";
        $this->params['publishTime'] = isset($this->inParams['publishTime'])?$this->inParams['publishTime']:"";
        $this->toUtf8();
        $this->params['signature']  = self::genSignature();

        return $res = self::sendCheck(env('TEXT_CHECK'));
    }
    public  function imgCheck()
    {
        $this->params["secretId"] = env('NETEASE_KEY');
        $this->params["businessId"] = env('IMG_BUSINESSID');
        $this->params["version"] = self::VERSION;
        $this->params["timestamp"] = sprintf("%d", round(microtime(true)*1000));// time in milliseconds
        $this->params["nonce"] = sprintf("%d", rand()); // random int
        $this->params['images'] = $this->inParams['images'];
        $this->params['ip'] = isset($this->inParams['ip'])?$this->inParams['ip']:"";
        $this->params['account'] = isset($this->inParams['account'])?$this->inParams['account']:"";
        $this->toUtf8();
        $this->params['signature']  = self::genSignature();

        return $res = self::sendCheck(env('IMG_CHECK'));


    }
    public function userCheck()
    {
        $this->params["secretId"] = env('NETEASE_KEY');
        $this->params["businessId"] = env('USER_BUSINESSID');
        $this->params["version"] = self::VERSION;
        $this->params["timestamp"] = sprintf("%d", round(microtime(true)*1000));// time in milliseconds
        $this->params["nonce"] = sprintf("%d", rand()); // random int
        $this->params['dataId'] = $this->inParams['dataId'];//设置为时间戳
        $this->params['content'] = $this->inParams['content'];
        $this->params['dataType'] = isset($this->inParams['dataType'])?$this->inParams['dataType']:"";
        $this->params['ip'] = isset($this->inParams['ip'])?$this->inParams['ip']:"";
        $this->params['account'] = isset($this->inParams['account'])?$this->inParams['account']:"";
        $this->params['deviceType'] = isset($this->inParams['deviceType'])?$this->inParams['deviceType']:"";
        $this->params['deviceId'] = isset($this->inParams['deviceId'])?$this->inParams['deviceId']:"";
        $this->params['callback'] = isset($this->inParams['callback'])?$this->inParams['callback']:"";
        $this->params['publishTime'] = isset($this->inParams['publishTime'])?$this->inParams['publishTime']:"";
        $this->toUtf8();
        $this->params['signature']  = self::genSignature();

        return $res = self::sendCheck(env('USER_CHECK'));
    }
    private  function genSignature()
    {
        ksort($this->params);
        $buff="";
        foreach($this->params as $key=>$value){
            if($value !== null ) {
                $buff .=$key;
                $buff .=$value;
            }
        }
        $buff .= env('NETEASE_SECRET');

        return md5(mb_convert_encoding($buff, "utf8", "auto"));

    }
    private function sendCheck($checkUrl){

        $client = new Client(['timeout'=>self::API_TIMEOUT]);
        try {
            $response = $client->request('POST', $checkUrl, [
                'form_params' =>
                    $this->params,

            ]);
        }catch (RequestException $e){

            return [
                "code"=>500,
                "msg"=>$e->getRequest()
            ];

        }
        $responseBody = json_decode($response->getBody(),true);

        if($responseBody === FALSE){
            //校验失败
            return array("code"=>500, "msg"=>"error");
        }else{

            return $responseBody;
        }

    }

    /**
     * 将输入数据的编码统一转换成utf8
     * @params 输入的参数
     */
    function toUtf8()
    {
        $utf8s = array();
        foreach ($this->params as $key => $value) {
            $this->params[$key] = is_string($value) ? mb_convert_encoding($value, "utf8", "auto") : $value;
        }
        //return $utf8s;
    }
}
