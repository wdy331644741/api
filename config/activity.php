<?php

return [
    //双十一活动
    'double_eleven' => [
        'key1' => 'double_eleven_default',
        'key2' => 'double_eleven_level1',
        'key3' => 'double_eleven_level2',
        'chance1' => 'double_eleven_chance1',
        'chance2' => 'double_eleven_chance2',
        'baotuan' => 'double_eleven_baotuan',
        'baotuan_probability' => 1,
        /*
        'baotuan_level' => [
            ['min'=> 2000,'max'=>4999, 'award'=>'30元红包', 'number' => 5],
            ['min'=> 5000, 'max'=>7999, 'award'=>'80元红包', 'number' => 10],
            ['min'=> 8000, 'max'=>9999, 'award'=>'100元宝贝格子礼品卡', 'number' => 20],
            ['min'=> 10000, 'max'=>11999, 'award'=>'夹克的虾套餐', 'number' => 30],
            ['min'=> 12000, 'max'=>1000000, 'award'=>'百度音乐vip', 'number' => 50],
        ]
        */
        'baotuan_level' => [
            ['min'=> 5,'max'=>4999, 'award'=>'30元红包', 'number' => 5],
            ['min'=> 10, 'max'=>7999, 'award'=>'80元红包', 'number' => 10],
            ['min'=> 15, 'max'=>9999, 'award'=>'100元宝贝格子礼品卡', 'number' => 15],
            ['min'=> 20, 'max'=>11999, 'award'=>'夹克的虾套餐', 'number' => 20],
            ['min'=> 25, 'max'=>1000000, 'award'=>'百度音乐vip', 'number' => 25],
        ]
    ],
    'rule_child'=>[
        0=>[
            'name'=>'注册时间',
            'model_name'=>'Register',
        ],
        1=>[
            'name'=>'用户渠道',
            'model_name'=>'Channel',
        ],
        2=>[
            'name'=>'是否邀请用户',
            'model_name'=>'Invite',
        ],
        3=>[
            'name'=>'邀请人数',
            'model_name'=>'Invitenum',
        ],
        4=>[
            'name'=>'用户等级',
            'model_name'=>'Userlevel',
        ],
        5=>[
            'name'=>'用户积分',
            'model_name'=>'Usercredit',
        ],
        6=>[
            'name'=>'用户余额',
            'model_name'=>'Balance',
        ],
        7=>[
            'name'=>'投资金额',
            'model_name'=>'Cast',
        ],
        8=>[
            'name'=>'充值金额',
            'model_name'=>'Recharge',
        ],
        9=>[
            'name'=>'回款金额',
            'model_name'=>'Payment',
        ],
        10=>[
            'name'=>'投资总金额',
            'model_name'=>'Castall',
        ],
        11=>[
            'name'=>'充值总金额',
            'model_name'=>'Rechargeall',
        ],
        12=>[
            'name'=>'标明',
            'model_name'=>'Castname',
        ],
        13=>[
            'name'=>'用户渠道黑名单',
            'model_name'=>'Channelblist',
        ],
        14=>[
            'name'=>'投资标类型',
            'model_name'=>'Casttype',
        ],
        15=>[
            'name'=>'参与人数',
            'model_name'=>'Joinnum',
        ]
    ],
    'activity_type'=>[
        0=>[
            'type_id'=>0,
            'type_name'=>'常规活动'
        ],
        1=>[
            'type_id'=>1,
            'type_name'=>'节日活动'
        ],
        2=>[
            'type_id'=>2,
            'type_name'=>'加急活动'
        ],
        3=>[
            'type_id'=>3,
            'type_name'=>'渠道活动'
        ],
    ],
    'content_type'=>[
        1=>[
            'type_id'=>1,
            'type_name'=>'常规活动'
        ],
        2=>[
            'type_id'=>2,
            'type_name'=>'网站公告'
        ],
        3=>[
            'type_id'=>3,
            'type_name'=>'网利动态'
        ],
    ],
    'double_eleven_start_time'=>'2016-09-28 00:00:00',//双十一活动开始时间
    'double_eleven_end_time'=>'2016-10-28 00:00:00'//双十一活动结束时间
];
