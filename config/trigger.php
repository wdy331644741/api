<?php
return [
    'trigger_http_url' => ENV("TRIGGER_HTTP_URL"),
    //触发配置
    'trigger'=>[
        0=>array(
            'name' => '主动',
            'model_name' => 'active',
        ),
        1=>array(
            'name' => '注册',
            'model_name' => 'register',
        ),
        2=>array(
            'name' => '充值',
            'model_name' => 'recharge',
        ),
        3=>array(
            'name' => '绑卡',
            'model_name' => 'bind_bank_card',
        ),
        4=>array(
            'name' => '投资',
            'model_name' => 'investment',
        ),
        5=>array(
            'name' => '回款',
            'model_name' => 'payment',
        ),
        6=>array(
            'name' => '实名',
            'model_name' => 'real_name',
        ),
        7=>array(
            'name' => '微信绑定',
            'model_name' => 'binding',
        ),
        8=>array(
            'name' => '签到',
            'model_name' => 'signin',
        )
    ]

];
