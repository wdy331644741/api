<?php
return [
    'alias_name' => 'sign_in_system',
    'invite_alias_name' => 'sign_in_system_invite_first',
    'trade_alias_name' => 'sign_in_system_threshold',
    'multiple_card_alias_name' => 'sign_in_system_multiple_card',
    'expired_hour' => 48,
    'interval' => 3, // 两次抽奖间隔秒数
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
            'start' => 11,
            'end' => 17,
            'times' => 60*15, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' => 'sign_in_system_0.1', 'size' => 0.1, 'num' => 3000, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.2', 'size' => 0.2, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.3', 'size' => 0.3, 'num' => 500, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.5', 'size' => 0.5, 'num' => 260, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.8', 'size' => 0.8, 'num' => 150, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_1',  'size' => 1, 'num' => 100, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_100', 'size' => 0, 'num' => 1000, 'is_rmb' => 0],
            ]
        ], [
            'start' => 17,
            'end' => 21,
            'times' => 60*15, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' => 'sign_in_system_0.1', 'size' => 0.1, 'num' => 3000, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.2', 'size' => 0.2, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.3', 'size' => 0.3, 'num' => 500, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.5', 'size' => 0.5, 'num' => 260, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.8', 'size' => 0.8, 'num' => 150, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_1',  'size' => 1, 'num' => 100, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_100', 'size' => 0, 'num' => 1000, 'is_rmb' => 0],
            ]
        ], [
            'start' => 21,
            'end' =>  11,
            'times' => 60*15, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' => 'sign_in_system_0.1', 'size' => 0.1, 'num' => 3000, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.2', 'size' => 0.2, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.3', 'size' => 0.3, 'num' => 500, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.5', 'size' => 0.5, 'num' => 260, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_0.8', 'size' => 0.8, 'num' => 150, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_1',  'size' => 1, 'num' => 100, 'is_rmb' => 1],
                ['alias_name' => 'sign_in_system_100', 'size' => 0, 'num' => 1000, 'is_rmb' => 0],
            ]
        ]
    ]
];
