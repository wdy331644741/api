<?php
namespace App\Service;

use App\Exceptions\OmgException;
use Lib\Curl;

class NetEastCheckService
{
    const NETEASE_KEY = '9e3ce496a575d530a73d82ade58d9614';
    const NETEASE_SECRET = '30651e878135ea74aba9b85f7ee810be';

    const TEXT_CHECK = 'https://api.aq.163.com/v3/text/check';
    const TEXT_BUSINESSID = 'ff308460bb83ff78fc3fe0ad24faa2e1';

    const IMG_CHECK='https://api.aq.163.com/v3/image/check';
    const IMG_BUSINESSID = 'cdc52d1661b50a99c8cc48e168a5d962';

    const VERSION = 'v3';
    const API_TIMEOUT=20;
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
        $this->params["secretId"] = self::NETEASE_KEY;
        $this->params["businessId"] = self::TEXT_BUSINESSID;
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

        return $res = self::sendCheck(self::TEXT_CHECK);
    }
    public  function imgCheck()
    {
        $this->params["secretId"] = self::NETEASE_KEY;
        $this->params["businessId"] = self::IMG_BUSINESSID;
        $this->params["version"] = self::VERSION;
        $this->params["timestamp"] = sprintf("%d", round(microtime(true)*1000));// time in milliseconds
        $this->params["nonce"] = sprintf("%d", rand()); // random int
        $this->params['images'] = $this->inParams['images'];
        $this->params['ip'] = isset($this->inParams['ip'])?$this->inParams['ip']:"";
        $this->params['account'] = isset($this->inParams['account'])?$this->inParams['account']:"";
        $this->toUtf8();
        $this->params['signature']  = self::genSignature();

        return $res = self::sendCheck(self::IMG_CHECK);


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
        $buff .= self::NETEASE_SECRET;

        return md5(mb_convert_encoding($buff, "utf8", "auto"));

    }
    private function sendCheck($checkUrl){
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'timeout' => self::API_TIMEOUT, // read timeout in seconds
                'content' => http_build_query($this->params),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($checkUrl, false, $context);

        if($result === FALSE){
            //校验失败
            return array("code"=>500, "msg"=>"file_get_contents failed.");
        }else{

            return json_decode($result, true);
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
