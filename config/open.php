<?php
/*
 * appid:公众号的唯一标识
 * redirect_uri:授权后重定向的回调链接地址，请使用urlencode对链接进行处理\
 * scope:应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
 * state:重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
 * secret:公众号的appsecret
 */
return [
    'weixin' => [
        'appid'=> 'wx169c6925918fe915',
        'scope'=>'snsapi_base',
        'state'=>'wanglibao',
        'secret'=>'777cd7abf2a7b63427a660fcec01383f',
        'api_base_uri'=>'https://api.weixin.qq.com',
        'open_base_uri'=>'https://open.weixin.qq.com',
        'msg_template' => [
            'wechat_bind'=>'8a21nArPQS0XWct6AGCASDqoaaaE_Ir5SaqarSqkNws',
            'wechat_unbind'=>'BR08JlAXbQ_JUCnKWmhrSNe4pNL2PF9PQxV1QLcxrNo',
            'sign_daily'=>'U0rO2rk56wviOegolXECWRlSlMo3V6g_GweOZ3a7spI',
            'recharge_success'=>env('WECHAT_RECHARGE_SUCCESS'),
            'withdraw_success'=>env('WECHAT_WITHDRAW_SUCCESS'),
            'get_account'=>env('WECHAT_GET_ACCOUNT'),
        ],
        'xml_template'=>[
            'textTpl'=>"<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                     </xml>"
        ]

        
    ],
    "duiba"=>[
        'AppKey'=>'3Pwf3saWFCjYCy9SXvgv81u3JrKM',
        'AppSecret'=>'4QJ2MaGnpigGW3zY4USZnixFH48Y',
        'Base_AutoLogin_Url'=>'http://www.duiba.com.cn/autoLogin/autologin?'
    ]
];