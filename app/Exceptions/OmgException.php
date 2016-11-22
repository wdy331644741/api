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
    const NO_LOGIN = 4004;

    //参数验证错误
    const VALID_POSITION_FAIL = 4100;
    const PARAMS_NOT_NULL = 4101;
    const PARAMS_ERROR = 4102;
    const VALID_CODE_FAIL = 4111;
    const VALID_USERID_FAIL = 4112;
    const VALID_NAME_FAIL = 4113;
    const VALID_PHONE_FAIL = 4114;
    const VALID_CITY_FAIL = 4115;
    const VALID_COLLATERAL_FAIL = 4116;
    const VALID_AMOUNT_FAIL = 4117;

    //服务器错误
    const GET_BANNER_FAIL = 4200;
    const GET_CODEDATAEMPTY_FAIL = 4201;
    const GET_AWARDDATAEMPTY_FAIL = 4202;
    const GET_AWARDDATAEXIST_FAIL = 4203;
    const SENDAEARD_FAIL = 4204;
    const PARAMS_NEED_ERROR = 4205;
    const DATABASE_ERROR = 4026;
    const FREQUENCY_ERROR = 4027;
    const INSERT_FAIL = 4028;
    const INTEGRAL_FAIL = 4029;
    const INTEGRAL_LACK_FAIL = 4030;
    const EXCEED_FAIL = 4031;
    const INTEGRAL_REMOVE_FAIL = 4032;

    //应用错误
    const NO_DATA = 4300;
    const ALREADY_SIGNIN = 4301;
    const ACTIVITY_NOT_EXIST = 4302;
    const NOT_SIGNIN = 4303;
    const PARSE_ERROR = 4304;
    const DAYS_NOT_ENOUGH = 4305;
    const ALREADY_AWARD = 4306;
    const ALREADY_SHARED = 4307;
    const IS_DINED_TO_WECHAT = 4308;
    const AWARD_NOT_EXIST = 4309;

    protected static $errorArray = array(
        //接口调用错误
        self::API_MIS_PARAMS => "缺少必要参数",
        self::API_ILLEGAL => "非法请求",
        self::API_BUSY => "接口调用过于频繁",
        self::API_FAILED => "接口调用失败",
        //参数验证错误
        self::VALID_POSITION_FAIL => "banner图位置不能为空",
        self::VALID_CODE_FAIL => "兑换码不能为空",
        self::PARAMS_ERROR => "参数错误",
        self::VALID_USERID_FAIL => "用户ID不能为空",
        self::VALID_NAME_FAIL => "名称不能为空",
        self::VALID_PHONE_FAIL => "手机号不能为空",
        self::VALID_CITY_FAIL => "城市不能为空",
        self::VALID_COLLATERAL_FAIL => "抵押物不能为空",
        self::VALID_AMOUNT_FAIL => "金额不能为空",
        self::PARAMS_NOT_NULL => "参数不能为空",
        self::PARAMS_NEED_ERROR => "缺少必要的参数",
        //服务器错误
        self::NO_LOGIN => "用户未登陆",
        self::GET_BANNER_FAIL => "获取banner图列表失败",
        self::GET_CODEDATAEMPTY_FAIL => "该CODE不存在或已发送",
        self::GET_AWARDDATAEMPTY_FAIL => "该CODE和奖品信息关系不存在或已过期",
        self::GET_AWARDDATAEXIST_FAIL => "该CODE和奖品信息关系必要数据为空",
        self::SENDAEARD_FAIL => "发送兑换码奖品失败",
        self::INSERT_FAIL => "插入失败",
        self::INTEGRAL_FAIL => "积分值配置错误",
        self::INTEGRAL_LACK_FAIL => "积分值不足",
        self::EXCEED_FAIL => "超过了兑换数量",
        self::INTEGRAL_REMOVE_FAIL => "积分扣除失败",
        self::EXCEED_NUM_FAIL => "超过了剩余次数",

        //应用错误
        self::NO_DATA => "暂无数据",
        self::ALREADY_SIGNIN => "今日已签到",
        self::DATABASE_ERROR => "数据库错误",
        self::ACTIVITY_NOT_EXIST => "活动不存在或已下线",
        self::NOT_SIGNIN => '今天还没有签到',
        self::PARSE_ERROR => '解析错误',
        self::DAYS_NOT_ENOUGH => '不满足领取条件',
        self::ALREADY_AWARD => '奖励已领取',
        self::ALREADY_SHARED => '已分享',
        self::FREQUENCY_ERROR => "您的错误次数太多，稍后再试",
        self::IS_DINED_TO_WECHAT =>"该账户已经绑定过微信，不能重复绑定",
        self::AWARD_NOT_EXIST => "奖品不存在"
    );

    public function __construct($code, $data = array())
    {
        $message = isset(self::$errorArray[$code]) ? self::$errorArray[$code] : 'error';
        parent::__construct($code, $message, $data);
    }
}


