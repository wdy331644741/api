<?php
namespace Lib;

use Config;
/**
 * Class Queue
 * @package Lib
 */
class McQueue
{
    private $errCode = 0;
    private $errMsg = '';

    public function put($tag, $data = [])
    {
        $postdata = ['tag' => $tag, 'data' => $data];
        return $this->send($postdata);
    }

    public function send($requestData){
        $curl = new \Lib\Curl\Curl();
        $url = Config::get("trigger.trigger_http_url");
        if (false !== stripos($url, 'https')) {
            //增加HTTPS支持
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER,false);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST,false);
        }
        $curl->post($url,json_encode($requestData));
        if($curl->httpStatusCode === 200)
        {
            $this->errCode = 0;
            $this->errMsg =  $curl->errorMessage;
            return true;
        }
        else
        {
            $this->errCode = $curl->errorCode;
            $this->errMsg =  $curl->errorMessage;
            return false;

        }
    }


    public function getErrCode(){
        return $this->errCode;
    }

    public function getErrMsg(){
        return $this->errMsg;
    }

    public function getErr(){
        return ['err_code' => $this->errCode ,'err_msg' => $this->errMsg];
    }



}