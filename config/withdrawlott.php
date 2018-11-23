<?php
return [
    'alias_name' => 'withdraw_lott',
    'alias_name_desc' => '抽奖',
    'interval' => 3, // 两次抽奖间隔秒数
    'drew_daily_key' => 'withdraw_lott_daily', // 两次抽奖间隔秒数
    'drew_total_key' => 'withdraw_lott_total', // 两次抽奖间隔秒数
    'lists' => [//每个会员等级的奖品概率
        /****************************v0*****************************************/
        0 =>[
            ['alias_name' =>'withdraw_again', 'desp' => '一次抽奖机会',  'size' => 1,       'pro' => 20],
            ['alias_name' =>'withdraw_8888_ex', 'desp' => '8888元体验金',   'size' => 8888, 'pro' => 2022222],
            ['alias_name' =>'withdraw_8_re', 'desp' => '8元直抵红包', 'size' => 8,          'pro' => 13],
            ['alias_name' =>'withdraw_48_re', 'desp' => '48元直抵红包', 'size' => 48,       'pro' => 15],
            ['alias_name' =>'withdraw_88_re', 'desp' => '88元直抵红包', 'size' => 88,       'pro' => 20],
            ['alias_name' =>'withdraw_10_in', 'desp' => '10%加息券', 'size' => 10,          'pro' => 5],
            ['alias_name' =>'withdraw_458_re', 'desp' => '458元直抵红包', 'size' => 458,    'pro' => 5],
            ['alias_name' =>'withdraw_1000_re', 'desp' => '1000元直抵红包', 'size' => 1000,  'pro' => 2],
            ['alias_name' =>'withdraw_8_cash', 'desp' => '8元现金', 'size' => 8,            'pro' => 0],
        ], 
        /****************************v1*****************************************/
        1 =>[
            ['alias_name' =>'withdraw_again', 'desp' => '一次抽奖机会',  'size' => 1,       'pro' => 20],
            ['alias_name' =>'withdraw_8888_ex', 'desp' => '8888元体验金',   'size' => 8888, 'pro' => 20],
            ['alias_name' =>'withdraw_8_re', 'desp' => '8元直抵红包', 'size' => 8,          'pro' => 13],
            ['alias_name' =>'withdraw_48_re', 'desp' => '48元直抵红包', 'size' => 48,       'pro' => 15],
            ['alias_name' =>'withdraw_88_re', 'desp' => '88元直抵红包', 'size' => 88,       'pro' => 20],
            ['alias_name' =>'withdraw_10_in', 'desp' => '10%加息券', 'size' => 10,          'pro' => 5],
            ['alias_name' =>'withdraw_458_re', 'desp' => '458元直抵红包', 'size' => 458,    'pro' => 5],
            ['alias_name' =>'withdraw_1000_re', 'desp' => '1000元直抵红包', 'size' => 1000,  'pro' => 2],
            ['alias_name' =>'withdraw_8_cash', 'desp' => '8元现金', 'size' => 8,            'pro' => 0],
        ], 
        /********************************v2-v3*************************************/
        2 =>[
            ['alias_name' =>'withdraw_again', 'desp' => '一次抽奖机会',  'size' => 1,       'pro' => 20],
            ['alias_name' =>'withdraw_8888_ex', 'desp' => '8888元体验金',   'size' => 8888, 'pro' => 20],
            ['alias_name' =>'withdraw_8_re', 'desp' => '8元直抵红包', 'size' => 8,          'pro' => 13],
            ['alias_name' =>'withdraw_48_re', 'desp' => '48元直抵红包', 'size' => 48,       'pro' => 15],
            ['alias_name' =>'withdraw_88_re', 'desp' => '88元直抵红包', 'size' => 88,       'pro' => 20],
            ['alias_name' =>'withdraw_10_in', 'desp' => '10%加息券', 'size' => 10,          'pro' => 5],
            ['alias_name' =>'withdraw_458_re', 'desp' => '458元直抵红包', 'size' => 458,    'pro' => 5],
            ['alias_name' =>'withdraw_1000_re', 'desp' => '1000元直抵红包', 'size' => 1000,  'pro' => 2],
            ['alias_name' =>'withdraw_8_cash', 'desp' => '8元现金', 'size' => 8,            'pro' => 0],
        ], 
        3 =>[
            ['alias_name' =>'withdraw_again', 'desp' => '一次抽奖机会',  'size' => 1,       'pro' => 20],
            ['alias_name' =>'withdraw_8888_ex', 'desp' => '8888元体验金',   'size' => 8888, 'pro' => 20],
            ['alias_name' =>'withdraw_8_re', 'desp' => '8元直抵红包', 'size' => 8,          'pro' => 13],
            ['alias_name' =>'withdraw_48_re', 'desp' => '48元直抵红包', 'size' => 48,       'pro' => 15],
            ['alias_name' =>'withdraw_88_re', 'desp' => '88元直抵红包', 'size' => 88,       'pro' => 20],
            ['alias_name' =>'withdraw_10_in', 'desp' => '10%加息券', 'size' => 10,          'pro' => 5],
            ['alias_name' =>'withdraw_458_re', 'desp' => '458元直抵红包', 'size' => 458,    'pro' => 5],
            ['alias_name' =>'withdraw_1000_re', 'desp' => '1000元直抵红包', 'size' => 1000,  'pro' => 2],
            ['alias_name' =>'withdraw_8_cash', 'desp' => '8元现金', 'size' => 8,            'pro' => 0],
        ], 
        /*********************************************************************/
        /*********************v4-v5************************************************/
        4 =>[
            ['alias_name' =>'withdraw_again', 'desp' => '一次抽奖机会',  'size' => 1,       'pro' => 20],
            ['alias_name' =>'withdraw_8888_ex', 'desp' => '8888元体验金',   'size' => 8888, 'pro' => 20],
            ['alias_name' =>'withdraw_8_re', 'desp' => '8元直抵红包', 'size' => 8,          'pro' => 13],
            ['alias_name' =>'withdraw_48_re', 'desp' => '48元直抵红包', 'size' => 48,       'pro' => 15],
            ['alias_name' =>'withdraw_88_re', 'desp' => '88元直抵红包', 'size' => 88,       'pro' => 20],
            ['alias_name' =>'withdraw_10_in', 'desp' => '10%加息券', 'size' => 10,          'pro' => 5],
            ['alias_name' =>'withdraw_458_re', 'desp' => '458元直抵红包', 'size' => 458,    'pro' => 5],
            ['alias_name' =>'withdraw_1000_re', 'desp' => '1000元直抵红包', 'size' => 1000,  'pro' => 2],
            ['alias_name' =>'withdraw_8_cash', 'desp' => '8元现金', 'size' => 8,            'pro' => 0],
        ], 
        5 =>[
            ['alias_name' =>'withdraw_again', 'desp' => '一次抽奖机会',  'size' => 1,       'pro' => 20],
            ['alias_name' =>'withdraw_8888_ex', 'desp' => '8888元体验金',   'size' => 8888, 'pro' => 20],
            ['alias_name' =>'withdraw_8_re', 'desp' => '8元直抵红包', 'size' => 8,          'pro' => 13],
            ['alias_name' =>'withdraw_48_re', 'desp' => '48元直抵红包', 'size' => 48,       'pro' => 15],
            ['alias_name' =>'withdraw_88_re', 'desp' => '88元直抵红包', 'size' => 88,       'pro' => 20],
            ['alias_name' =>'withdraw_10_in', 'desp' => '10%加息券', 'size' => 10,          'pro' => 5],
            ['alias_name' =>'withdraw_458_re', 'desp' => '458元直抵红包', 'size' => 458,    'pro' => 5],
            ['alias_name' =>'withdraw_1000_re', 'desp' => '1000元直抵红包', 'size' => 1000,  'pro' => 2],
            ['alias_name' =>'withdraw_8_cash', 'desp' => '8元现金', 'size' => 8,            'pro' => 0],
        ], 
        /*********************************************************************/
    ],
    // 'specialAward' =>['alias_name' =>'oct_iPhoneX', 'desp' => 'iPhoneX', 'num' => 1, 'totalCounts' => 0],
];
