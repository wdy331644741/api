<?php
return [
    'alias_name' => 'rich_lottery',
    'invite_alias_name' => 'sign_in_system_invite_first',
    'trade_alias_name' => 'sign_in_system_threshold',
    'multiple_card_alias_name' => 'sign_in_system_multiple_card',
    'expired_hour' => 48,
    'interval' => 5, // 两次抽奖间隔秒数
    'award_number_multiple' => 1,  //奖品数量倍率
    'multipleLists' => [
        [
            'min' => 0,
            'max' => 0,
            'multiple' => 1,
        ], [
            'min' => 1,
            'max' => 3,
            'multiple' => 1.2,
        ], [
            'min' => 4,
            'max' => 6,
            'multiple' => 1.5,
        ], [
            'min' => 7,
            'max' => 9,
            'multiple' => 2,
        ], [
            'min' => 10,
            'max' => 1000,
            'multiple' => 3,
        ]

    ],
    'lists' => [
        [
            'start' => 9,
            'end' => 15,
            'times' => 60*60, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' =>'rich_lottery_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 10, 'award_type' => 0],
                ['alias_name' =>'rich_lottery_8.8_in', 'desp' => '8.8%加息券', 'size' => 8.8, 'pro' => 20, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_8.8_re', 'desp' => '8.8元红包', 'size' => 8.8, 'pro' => 20, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_1_in', 'desp' => '1%加息券', 'size' => 1, 'pro' => 10, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 10, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_1.5_in', 'desp' => '1.5加息券', 'size' => 1.5, 'pro' => 10, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_888_re', 'desp' => '888元红包', 'size' => 888, 'pro' => 15, 'award_type' => 1],
                ['alias_name' =>'thanks', 'desp' => '谢谢参与', 'size' => 0, 'pro' => 5, 'award_type' => 0],
            ]
        ], [
            'start' => 15,
            'end' => 18,
            'times' => 60*60, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' =>'rich_lottery_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 5, 'award_type' => 0],
                ['alias_name' =>'rich_lottery__8.8_in', 'desp' => '8.8%加息券', 'size' => 8.8, 'pro' => 15, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_8.8_re', 'desp' => '8.8元红包', 'size' => 8.8, 'pro' => 15, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_1_in', 'desp' => '1%加息券', 'size' => 1, 'pro' => 15, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 20, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_1.5_in', 'desp' => '1.5加息券', 'size' => 1.5, 'pro' => 15, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_888_re', 'desp' => '888元红包', 'size' => 888, 'pro' => 15, 'award_type' => 1],
                // ['alias_name' =>'', 'desp' => '谢谢参与', 'size' => 0, 'pro' => 5, 'award_type' => 0],

            ]
        ], [
            'start' => 18,
            'end' =>  9,
            'times' => 60*60, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' =>'rich_lottery_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 10, 'award_type' => 0],
                ['alias_name' =>'rich_lottery_8.8_in', 'desp' => '8.8%加息券', 'size' => 8.8, 'pro' => 15, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_8.8_re', 'desp' => '8.8元红包', 'size' => 8.8, 'pro' => 10, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_1_in', 'desp' => '1%加息券', 'size' => 1, 'pro' => 20, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 20, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_1.5_in', 'desp' => '1.5加息券', 'size' => 1.5, 'pro' => 10, 'award_type' => 1],
                ['alias_name' =>'rich_lottery_888_re', 'desp' => '888元红包', 'size' => 888, 'pro' => 10, 'award_type' => 1],
                ['alias_name' =>'thanks', 'desp' => '谢谢参与', 'size' => 0, 'pro' => 5, 'award_type' => 0],
            ]
        ]
    ]
];
