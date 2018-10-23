<?php
return [
    'alias_name' => 'oct_lottery',
    'alias_name_desc' => '十月份抽奖',
    'interval' => 3, // 两次抽奖间隔秒数
    'drew_daily_key' => 'oct_drew_daily', // 两次抽奖间隔秒数
    'drew_total_key' => 'oct_drew_total', // 两次抽奖间隔秒数
    'lists' => [//每个会员等级的奖品概率
        /****************************v0*****************************************/
        0 =>[
            ['alias_name' =>'oct_6666_ex', 'desp' => '6666元体验金', 'size' => 18888, 'pro' => 25],
            ['alias_name' =>'oct_66_re', 'desp' => '66元直抵红包',  'size' => 88, 'pro' => 15],
            ['alias_name' =>'oct_136_re', 'desp' => '136元直抵红包', 'size' => 588, 'pro' => 10],
            ['alias_name' =>'oct_366_re', 'desp' => '366元直抵红包', 'size' => 1088, 'pro' => 5],
            ['alias_name' =>'oct_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 20],
            ['alias_name' =>'oct_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 10],
            ['alias_name' =>'oct_66_po', 'desp' => '66积分', 'size' => 10, 'pro' => 15],
        ], 
        /****************************v1*****************************************/
        1 =>[
            ['alias_name' =>'oct_6666_ex', 'desp' => '6666元体验金', 'size' => 18888, 'pro' => 20],
            ['alias_name' =>'oct_66_re', 'desp' => '66元直抵红包',  'size' => 88, 'pro' => 20],
            ['alias_name' =>'oct_136_re', 'desp' => '136元直抵红包', 'size' => 588, 'pro' => 20],
            ['alias_name' =>'oct_366_re', 'desp' => '366元直抵红包', 'size' => 1088, 'pro' => 5],
            ['alias_name' =>'oct_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 10],
            ['alias_name' =>'oct_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 10],
            ['alias_name' =>'oct_66_po', 'desp' => '66积分', 'size' => 10, 'pro' => 15],
        ], 
        /********************************v2-v3*************************************/
        2 =>[
            ['alias_name' =>'oct_6666_ex', 'desp' => '6666元体验金', 'size' => 18888, 'pro' => 5],
            ['alias_name' =>'oct_66_re', 'desp' => '66元直抵红包',  'size' => 88, 'pro' => 10],
            ['alias_name' =>'oct_136_re', 'desp' => '136元直抵红包', 'size' => 588, 'pro' => 25],
            ['alias_name' =>'oct_366_re', 'desp' => '366元直抵红包', 'size' => 1088, 'pro' => 25],
            ['alias_name' =>'oct_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 20],
            ['alias_name' =>'oct_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 15],
            ['alias_name' =>'oct_66_po', 'desp' => '66积分', 'size' => 10, 'pro' => 0],
        ], 
        3 =>[
            ['alias_name' =>'oct_6666_ex', 'desp' => '6666元体验金', 'size' => 18888, 'pro' => 5],
            ['alias_name' =>'oct_66_re', 'desp' => '66元直抵红包',  'size' => 88, 'pro' => 10],
            ['alias_name' =>'oct_136_re', 'desp' => '136元直抵红包', 'size' => 588, 'pro' => 25],
            ['alias_name' =>'oct_366_re', 'desp' => '366元直抵红包', 'size' => 1088, 'pro' => 25],
            ['alias_name' =>'oct_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 20],
            ['alias_name' =>'oct_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 15],
            ['alias_name' =>'oct_66_po', 'desp' => '66积分', 'size' => 10, 'pro' => 0],
        ], 
        /*********************************************************************/
        /*********************v4-v5************************************************/
        4 =>[
            ['alias_name' =>'oct_6666_ex', 'desp' => '6666元体验金', 'size' => 18888, 'pro' => 5],
            ['alias_name' =>'oct_66_re', 'desp' => '66元直抵红包',  'size' => 88, 'pro' => 15],
            ['alias_name' =>'oct_136_re', 'desp' => '136元直抵红包', 'size' => 588, 'pro' => 30],
            ['alias_name' =>'oct_366_re', 'desp' => '366元直抵红包', 'size' => 1088, 'pro' => 30],
            ['alias_name' =>'oct_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 5],
            ['alias_name' =>'oct_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 15],
            ['alias_name' =>'oct_66_po', 'desp' => '66积分', 'size' => 10, 'pro' => 0],
        ], 
        5 =>[
            ['alias_name' =>'oct_6666_ex', 'desp' => '6666元体验金', 'size' => 18888, 'pro' => 5],
            ['alias_name' =>'oct_66_re', 'desp' => '66元直抵红包',  'size' => 88, 'pro' => 15],
            ['alias_name' =>'oct_136_re', 'desp' => '136元直抵红包', 'size' => 588, 'pro' => 30],
            ['alias_name' =>'oct_366_re', 'desp' => '366元直抵红包', 'size' => 1088, 'pro' => 30],
            ['alias_name' =>'oct_1_in', 'desp' => '1.0%加息券', 'size' => 1, 'pro' => 5],
            ['alias_name' =>'oct_1.5_in', 'desp' => '1.5%加息券', 'size' => 1.5, 'pro' => 15],
            ['alias_name' =>'oct_66_po', 'desp' => '66积分', 'size' => 10, 'pro' => 0],
        ], 
        /*********************************************************************/
    ],
    'specialAward' =>['alias_name' =>'oct_iPhoneX', 'desp' => 'iPhoneX', 'num' => 1, 'totalCounts' => 0],
];
