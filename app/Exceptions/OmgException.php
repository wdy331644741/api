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
    const API_LIMIT = 4015;

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
    const CITY_IS_TOO_LONG = 4118;
    const REALNAME_IS_TOO_LONG = 4119;
    const ADDRESS_IS_TOO_LONG = 4120;
    const AREA_IS_TOO_BIG = 4121;
    const NAME_IS_ALIVE = 4122;
    const ADDRESS_IS_ALIVE = 4123;
    const VALID_AMOUNT_ERROR = 4130;

    //服务器错误
    const GET_BANNER_FAIL = 4200;
    const GET_CODEDATAEMPTY_FAIL = 4201;
    const GET_AWARDDATAEMPTY_FAIL = 4202;
    const GET_AWARDDATAEXIST_FAIL = 4203;
    const SENDAEARD_FAIL = 4204;
    const PARAMS_NEED_ERROR = 4205;
    const REDEEM_EMPTY = 4206;
    const REDEEM_IS_GET = 4207;
    const DATABASE_ERROR = 4026;
    const FREQUENCY_ERROR = 4027;
    const INSERT_FAIL = 4028;
    const INTEGRAL_FAIL = 4029;
    const INTEGRAL_LACK_FAIL = 4030;
    const EXCEED_FAIL = 4031;
    const INTEGRAL_REMOVE_FAIL = 4032;
    const EXCEED_NUM_FAIL = 4033;
    const EXCEED_USER_NUM_FAIL = 4034;
    const ONEYUAN_FULL_FAIL = 4035;
    const EXCEED_DAY_LIMIT = 4036;

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
    const MALL_NOT_EXIST = 4310;
    const MALL_IS_HAS = 4311;
    const DATA_ERROR = 4312;
    const SEND_ERROR = 4313;
    const NUMBER_IS_NULL = 4314;
    const CONDITION_NOT_ENOUGH = 4315;
    const ACTIVITY_IS_END = 4316;
    const ALREADY_EXIST = 4317;

    const NICKNAME_ERROR = 4318;
    const NICKNAME_REPEAT = 4319;
    const RIGHT_ERROR = 4320;
    const NICKNAME_IS_NULL = 4321;
    const THREAD_ERROR = 4322;
    const COMMENT_ERROR = 4323;
    const THREAD_LIMIT = 4324;
    const COMMENT_LIMIT = 4325;

    const HELP_ERROR = 4326;
    const EXCHANGE_ERROR = 4327;

    const TODAY_ACTIVITY_IS_END = 4328;

    const TODAY_IS_RECEIVE = 4329;
    const TODAY_REDPACK_IS_NULL = 4330;
    const NOT_RECEIVE_MY_REDPACK = 4331;
    const TODAY_OLDUSER_RECEIVE_IS_MORE = 4332;
    const UNBIND_USER_ONLY_RECEIVE_ONE = 4333;
    const LINK_IS_INVALID = 4334;

    const CUSTOM_AWARD = 4340;
    const CONFIG_NULL = 4341;
    const GET_ERROR_DATA = 4342;
    const ACTIVITY_NOT_JOIN = 4351;
    const FUND_LACK_FAIL = 4354;

    protected static $errorArray = array(
        //接口调用错误
        self::API_MIS_PARAMS => "缺少必要参数",
        self::API_ILLEGAL => "非法请求",
        self::API_BUSY => "接口调用过于频繁",
        self::API_FAILED => "接口调用失败",
        self::API_LIMIT => "操作过于频繁",

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
        self::CITY_IS_TOO_LONG => "城市名过长",
        self::REALNAME_IS_TOO_LONG => "产权人名字过长",
        self::ADDRESS_IS_TOO_LONG => "地址过长",
        self::AREA_IS_TOO_BIG => "面积无法计算",
        self::NAME_IS_ALIVE => "用户名含有敏感字符",
        self::ADDRESS_IS_ALIVE => "地址含有敏感字符",
        self::VALID_AMOUNT_ERROR => "金额不正确",

        //服务器错误
        self::NO_LOGIN => "用户未登陆",
        self::GET_BANNER_FAIL => "获取banner图列表失败",
        self::GET_CODEDATAEMPTY_FAIL => "该CODE不存在或已发送",
        self::GET_AWARDDATAEMPTY_FAIL => "该CODE和奖品信息关系不存在或已过期",
        self::GET_AWARDDATAEXIST_FAIL => "该CODE和奖品信息关系必要数据为空",
        self::SENDAEARD_FAIL => "发送兑换码奖品失败",
        self::REDEEM_EMPTY => "口令红包已领取完",
        self::REDEEM_IS_GET => "该口令红包您已经领取",
        self::INSERT_FAIL => "插入失败",
        self::INTEGRAL_FAIL => "商品信息有误，停止兑换",
        self::INTEGRAL_LACK_FAIL => "积分值不足",
        self::EXCEED_FAIL => "超出兑换次数限制",
        self::EXCEED_DAY_LIMIT => "今日兑换次数已达上限",
        self::INTEGRAL_REMOVE_FAIL => "兑换失败",
        self::EXCEED_NUM_FAIL => "库存不足",
        self::EXCEED_USER_NUM_FAIL => "用户次数不足",
        self::ONEYUAN_FULL_FAIL => "该奖品已经参与满",

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
        self::AWARD_NOT_EXIST => "奖品不存在",
        self::MALL_NOT_EXIST => "奖品不存在",
        self::MALL_IS_HAS => "已经领取过该奖品",
        self::DATA_ERROR => "数据有误",
        self::SEND_ERROR => "发送失败",
        self::NUMBER_IS_NULL =>"剩余数量不足",
        self::CONDITION_NOT_ENOUGH => "条件不足",
        self::ACTIVITY_IS_END => "活动已结束",
        self::ALREADY_EXIST => "数据已存在",
        self::NICKNAME_ERROR=>"昵称不符合规则",
        self::NICKNAME_REPEAT=>"该昵称已存在",
        self::RIGHT_ERROR=>"您没有操作权限",
        self::NICKNAME_IS_NULL=>"昵称不能为空",
        self::THREAD_ERROR =>"抱歉，发贴失败，因含有敏感词等",
        self::COMMENT_ERROR=>"抱歉，评论失败，因含有敏感词等",
        self::THREAD_LIMIT=>"今日发贴已达上限，明天再来吧！",
        self::COMMENT_LIMIT=>"今日评论已达上限，明天再来吧！",

        self::HELP_ERROR=>"一天只能为一名好友助力一次呦～",
        self::EXCHANGE_ERROR=>"已经兑换",
        self::TODAY_ACTIVITY_IS_END => "今天活动已结束",

        self::TODAY_IS_RECEIVE => "今日已领取",
        self::TODAY_REDPACK_IS_NULL => "今日已领完",
        self::NOT_RECEIVE_MY_REDPACK => "不能领取自己的红包",
        self::TODAY_OLDUSER_RECEIVE_IS_MORE => "领取老用户红包过多",
        self::UNBIND_USER_ONLY_RECEIVE_ONE => "未绑卡用户活动期间只能领取一次",
        self::LINK_IS_INVALID => "链接失效",

        self::CUSTOM_AWARD => "相同期限福利券已存在，请重新定制",
        self::CONFIG_NULL => "未找到配置数据",
        self::GET_ERROR_DATA => "请求passport数据有误",
        self::ACTIVITY_NOT_JOIN => "请参与活动后再来兑换奖品",
        self::FUND_LACK_FAIL => "您的踏青基金不足",

    );

    public function __construct($code, $data = array())
    {
        $message = isset(self::$errorArray[$code]) ? self::$errorArray[$code] : 'error';
        parent::__construct($code, $message, $data);
    }
}


