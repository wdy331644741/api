<?php
return [
    'reward_http_url' => env('REWARD_HTTP_URL'),
    'rulecheck_user_http_url' => env('RULECHECK_USER_HTTP_URL'),
    //项目期限类型
    'project_duration_type'=>[
        1=>'不限',
        2=>'月',
        3=>'月及以上',
        4=>'月及以下',
        5=>'日',
        6=>'日及以上',
        7=>'日及以下'
    ]
];
