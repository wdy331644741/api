<?php
return [
    'alias_name' => 'fouryear_pre',
    'alias_name_desc' => '四周年积分抽iphonx',
    'drew_cost' => 1,//1积分抽奖
    'interval' => 3, // 两次抽奖间隔秒数
    'drew_daily_key' => 'richLottery_drew_daily', // 两次抽奖间隔秒数
    'drew_total_key' => 'richLottery_drew_total', // 两次抽奖间隔秒数
    'lists' => [//每个会员等级的奖品概率
        /****************************v0*****************************************/
        0 =>[
            ['alias_name' =>'fouryear_pre_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 15],
            ['alias_name' =>'fouryear_pre_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_588_re', 'desp' => '588元红包', 'size' => 588, 'pro' => 1],
            ['alias_name' =>'fouryear_pre_1088_re', 'desp' => '1088元红包', 'size' => 1088, 'pro' => 1],
            ['alias_name' =>'fouryear_pre_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 30],
            ['alias_name' =>'fouryear_pre_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 23],
            ['alias_name' =>'fouryear_pre_10_po', 'desp' => '10积分', 'size' => 10, 'pro' => 20],
        ], 
        /****************************v1*****************************************/
        1 =>[
            ['alias_name' =>'fouryear_pre_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_588_re', 'desp' => '588元红包', 'size' => 588, 'pro' => 1],
            ['alias_name' =>'fouryear_pre_1088_re', 'desp' => '1088元红包', 'size' => 1088, 'pro' => 1],
            ['alias_name' =>'fouryear_pre_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 33],
            ['alias_name' =>'fouryear_pre_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 30],
            ['alias_name' =>'fouryear_pre_10_po', 'desp' => '10积分', 'size' => 10, 'pro' => 15],
        ], 
        /********************************v2-v3*************************************/
        2 =>[
            ['alias_name' =>'fouryear_pre_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_588_re', 'desp' => '588元红包', 'size' => 588, 'pro' => 2],
            ['alias_name' =>'fouryear_pre_1088_re', 'desp' => '1088元红包', 'size' => 1088, 'pro' => 2],
            ['alias_name' =>'fouryear_pre_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 35],
            ['alias_name' =>'fouryear_pre_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 31],
            ['alias_name' =>'fouryear_pre_10_po', 'desp' => '10积分', 'size' => 10, 'pro' => 10],
        ], 
        3 =>[
            ['alias_name' =>'fouryear_pre_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_588_re', 'desp' => '588元红包', 'size' => 588, 'pro' => 2],
            ['alias_name' =>'fouryear_pre_1088_re', 'desp' => '1088元红包', 'size' => 1088, 'pro' => 2],
            ['alias_name' =>'fouryear_pre_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 35],
            ['alias_name' =>'fouryear_pre_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 31],
            ['alias_name' =>'fouryear_pre_10_po', 'desp' => '10积分', 'size' => 10, 'pro' => 10],
        ], 
        /*********************************************************************/
        /*********************v4-v5************************************************/
        4 =>[
            ['alias_name' =>'fouryear_pre_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_588_re', 'desp' => '588元红包', 'size' => 588, 'pro' => 5],
            ['alias_name' =>'fouryear_pre_1088_re', 'desp' => '1088元红包', 'size' => 1088, 'pro' => 5],
            ['alias_name' =>'fouryear_pre_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 35],
            ['alias_name' =>'fouryear_pre_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 25],
            ['alias_name' =>'fouryear_pre_10_po', 'desp' => '10积分', 'size' => 10, 'pro' => 10],
        ], 
        5 =>[
            ['alias_name' =>'fouryear_pre_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 10],
            ['alias_name' =>'fouryear_pre_588_re', 'desp' => '588元红包', 'size' => 588, 'pro' => 5],
            ['alias_name' =>'fouryear_pre_1088_re', 'desp' => '1088元红包', 'size' => 1088, 'pro' => 5],
            ['alias_name' =>'fouryear_pre_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 35],
            ['alias_name' =>'fouryear_pre_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 25],
            ['alias_name' =>'fouryear_pre_10_po', 'desp' => '10积分', 'size' => 10, 'pro' => 10],
        ], 
        /*********************************************************************/
    ],
    'specialAward' =>['alias_name' =>'fouryear_pre_iPhoneX', 'desp' => 'iPhoneX', 'num' => 1, 'totalCounts' => 1340],
];
