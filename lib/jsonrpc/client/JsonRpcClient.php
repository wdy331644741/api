<?php
namespace Lib;
class JsonRpcClient
{
    private $_rpcUrl; //rpc服务地址
    private $_userHeaders; //用户请求头
    private $_config = [
        'timeout' => 20,
        'resultToArr' => true,
        'useCurrentCookie' => true,
        'cookie' => '',
        'useCurrentUserAgent' => true,
        'useCurrentReferer' => true,
    ]; //rpc配置
    private $_requestParams = [
        'timeout' => 20,
        'resultToArr' => true,
        'Cookie' => '',
        'User-Agent' => '',
        'Referer' => '',
    ]; //rpc请求参数

    public function __construct($rpcUrl, $config = [])
    {
        $this->_rpcUrl = $rpcUrl;
        $this->_config['timeout']  = isset($config['timeout']) ? $config['timeout'] : $this->_config['timeout'];
        $this->_config['resultToArr']  = isset($config['resultToArr']) ? $config['resultToArr'] : $this->_config['timeout'];
        $this->_config['useCurrentCookie']  = isset($config['useCurrentCookie']) ? $config['useCurrentCookie'] : $this->_config['useCurrentCookie'];
        $this->_config['cookie']  = isset($config['cookie']) ? $config['cookie'] : $this->_config['cookie'];
        $this->_config['useCurrentUserAgent']  = isset($config['useCurrentUserAgent']) ? $config['useCurrentUserAgent'] : $this->_config['useCurrentUserAgent'];
        $this->_config['useCurrentReferer']  = isset($config['useCurrentReferer']) ? $config['useCurrentReferer'] : $this->_config['useCurrentReferer'];
        $this->_initRequestParams();
    }

    private function _initRequestParams(){
        $this->_getAllHeaders();
        $this->setTimeout($this->_config['timeout']);
        $this->_requestParams['resultToArr'] = $this->_config['resultToArr'];
        $this->_setCookies();
        if($this->_config['useCurrentUserAgent'])
            $this->_requestParams['User-Agent'] = isset($this->_userHeaders['User-Agent']) ? $this->_userHeaders['User-Agent'] : $this->_requestParams['User-Agent'];
        if($this->_config['useCurrentReferer'])
            $this->_requestParams['Referer'] = isset($this->_userHeaders['Referer']) ? $this->_userHeaders['Referer'] : $this->_requestParams['Referer'];
    }

    /**
     * 设置jsonRPC调用超时时长
     * @param $s int
     */
    public function setTimeout($s)
    {
        if($s)
        {
            $this->_requestParams['timeout'] = $s;
            @set_time_limit($s + 2); //rpc请求超时后，给出两秒时间让程序处理超时的逻辑

        }
        else
        {
            $this->_requestParams['timeout'] = PHP_INT_MAX;
            @set_time_limit(0);
        }

    }

    private function _setCookies(){
        if($this->_config['useCurrentCookie'])
        {
            $this->_requestParams['Cookie'] = isset($this->_userHeaders['Cookie']) ? $this->_userHeaders['Cookie'] : '';
        }
        else
        {
            $this->_requestParams['Cookie'] = $this->_config['cookie'];
        }
    }


    /**
     * 获取用户请求的headers
     * @return array
     */
    private function _getAllHeaders(){
        if(!$this->_userHeaders)
        {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $this->_userHeaders[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
    }

    public function __call($name, $arguments)
    {
        return $this->call(new RpcRequest($name, $arguments));
    }

    public function call($rpcRequest)
    {
        if ($rpcRequest instanceof RpcRequest) {
            try {
                return $this->httpRequest($rpcRequest->getRpcRequestObject());
            } catch (\Exception $e) {
                $result =  $result = ["jsonrpc" => "2.0", "error" => ["code" => -1, "message" => $e->getMessage()], "id" => "1"];
                if (!$this->_requestParams['resultToArr']) {
                    return json_decode(json_encode($result));
                } else {
                    return $result;
                }

            }

        }
    }

    public function callBatch($rpcRequestList)
    {
        $rpcBatchArray = array();
        foreach ($rpcRequestList as $rpcRequest) {
            if ($rpcRequest instanceof RpcRequest) {
                array_push($rpcBatchArray, $rpcRequest->getRpcRequestObject());
            }
        }
        try {
            return $this->httpRequest($rpcBatchArray);
        } catch (\Exception $e) {
            $result = ["jsonrpc" => "2.0", "error" => ["code" => -1, "message" => $e->getMessage()], "id" => "1"];
            if (!$this->_requestParams['resultToArr']) {
                return json_decode(json_encode($result));
            } else {
                return $result;
            }
        }

    }



    private function httpRequest($rpcBatchArray)
    {
        $curl = new \Lib\Curl\Curl();
        $curl->setOpt(CURLINFO_CONTENT_TYPE,"application/json");
        if (false !== stripos($this->_rpcUrl, 'https')) {
            //增加HTTPS支持
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER,false);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST,false);
        }
        $curl->setHeader('Cookie', $this->_requestParams['Cookie']);
        $curl->setUserAgent($this->_requestParams['User-Agent']);
        $curl->setReferer($this->_requestParams['Referer']);
        $curl->setConnectTimeout(10);
        $curl->setTimeout($this->_requestParams['timeout']);
        $curl->post($this->_rpcUrl,json_encode($rpcBatchArray));
        $response = $curl->rawResponse;
        if (!$response) {
            throw new \Exception('Curl error: ' . $curl->errorCode . ':' .$curl->error , $curl->errorCode);
        }
        $curl->close();
        $json_response = json_decode($response, $this->_requestParams['resultToArr']); //增加转换为数组开关
        if (json_last_error() != JSON_ERROR_NONE) {
            switch (json_last_error()) {
                case JSON_ERROR_DEPTH:
                    $message = 'The maximum stack depth has been exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $message = 'Invalid or malformed JSON';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $message = 'Control character error, possibly incorrectly encoded';
                    break;
                case JSON_ERROR_SYNTAX:
                    $message = 'Syntax error';
                    break;
                case JSON_ERROR_UTF8:
                    $message = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    $message = "Error decoding JSON string.";
                    break;
            }
            $message .= "\nMethod: " . $rpcBatchArray->method .
                "\nParams: " . var_export($rpcBatchArray->params, true) .
                "\nResponse: " . $response;
            throw new \Exception($message, json_last_error());
        }
        return $json_response;
    }
}
