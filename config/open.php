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
        'redirect_uri'=>'http://f1e07acd.ngrok.io/',
        'scope'=>'snsapi_base',
        'state'=>'wanglibao',
        'secret'=>'777cd7abf2a7b63427a660fcec01383f',
        'api_base_uri'=>'https://api.weixin.qq.com',
        'open_base_uri'=>'https://open.weixin.qq.com',
        'msg_template' => [
            'wechat_bind'=>'pI8t87TM7pcYuPX95798SpY6MHihe0dKoT85y3g0yIk',
            'wechat_unbind'=>'S-xfemyge3RBsJQfBRvfnLovFkZ3ZwxLMY5dngGx1kI',
            'sign_daily'=>'UrrX3l-ORVFEPEQfsZzcCXpaaT1z9AeeTW1McqseLZU',
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

        
    ]
];