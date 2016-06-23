<?php
/**
 * User: neil
 * Date: 16/5/5
 * Time: 下午2:18
 */
namespace App\Exceptions;
use Lib\JsonRpcBasicErrorException as BasicException;


class OmgException extends BasicException
{
    /**
     * 错误分类
     * 40xx 接口调用错误
     * 41xx 参数验证错误
     * 42xx 服务器错误
     */

    //接口调用错误
    const API_MIS_PARAMS = 4000;
    const API_ILLEGAL = 4001;
    const API_BUSY = 4002;
    const API_FAILED = 4003;

    //参数验证错误
    const VALID_POSITION_FAIL = 4100;

    //服务器错误
    const GET_BANNER_FAIL = 4200;

    protected static $errorArray = array(
        //接口调用错误
        self::API_MIS_PARAMS => "缺少必要参数",
        self::API_ILLEGAL => "非法请求",
        self::API_BUSY => "接口调用过于频繁",
        self::API_FAILED => "接口调用失败",
        //参数验证错误
        self::VALID_POSITION_FAIL => "banner图位置不能为空",
        //服务器错误
        self::GET_BANNER_FAIL => "获取banner图列表失败",
    );

    public function __construct($code, $data = array())
    {
        $message = isset(self::$errorArray[$code]) ? self::$errorArray[$code] : 'error';
        parent::__construct($code, $message, $data);
    }
}


