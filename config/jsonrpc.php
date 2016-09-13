<?php
return [
    'server' => [
        'account' => [
            'url' => env('ACCOUNT_HTTP_URL'),
            'config' => [
                'timeout' => 20,//请求超时时间，默认20秒
                'resultToArr' => true, //结果以数组返回
                'useCurrentCookie' => true, //使用用户浏览器cookies
                'cookie' => '', // 如果不使用用户浏览器的cookies，可以自定义cookies，默认空。
                'useCurrentUserAgent' => true, //使用用户浏览器信息
                'useCurrentReferer' => true, //使用用户上一页面来源
            ],
        ] ,
        'inside' => [
            'url' => env('INSIDE_HTTP_URL'),
            'config' => [
                'timeout' => 20,//请求超时时间，默认20秒
                'resultToArr' => true, //结果以数组返回
                'useCurrentCookie' => true, //使用用户浏览器cookies
                'cookie' => '', // 如果不使用用户浏览器的cookies，可以自定义cookies，默认空。
                'useCurrentUserAgent' => true, //使用用户浏览器信息
                'useCurrentReferer' => true, //使用用户上一页面来源
            ],
        ] ,
    ],

];