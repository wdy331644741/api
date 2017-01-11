<?php
return [
    'alias_name' => 'yaoyiyao',
    'invite_alias_name' => 'shake_to_shake_invite_first',
    'interval' => 3, // 两次抽奖间隔秒数
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
            'start' => 10,
            'end' => 17,
            'times' => 3600*6, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' => 'yaoyiyao_0.1', 'size' => 0.1, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.2', 'size' => 0.2, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.3', 'size' => 0.3, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.5', 'size' => 0.5, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.8', 'size' => 0.8, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_1',  'size' => 1, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_2', 'size' => 0, 'num' => 1000, 'is_rmb' => 0],
                ['alias_name' => 'yaoyiyao_100', 'size' => 0, 'num' => 1000, 'is_rmb' => 0],
            ]
        ], [
            'start' => 17,
            'end' => 21,
            'times' => 3600*4, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' => 'yaoyiyao_0.1', 'size' => 0.1, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.2', 'size' => 0.2, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.3', 'size' => 0.3, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.5', 'size' => 0.5, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.8', 'size' => 0.8, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_1',  'size' => 1, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_2', 'size' => 0, 'num' => 1000, 'is_rmb' => 0],
                ['alias_name' => 'yaoyiyao_100', 'size' => 0, 'num' => 1000, 'is_rmb' => 0],
            ]
        ], [
            'start' => 21,
            'end' =>  10,
            'times' => 3600*3, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' => 'yaoyiyao_0.1', 'size' => 0.1, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.2', 'size' => 0.2, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.3', 'size' => 0.3, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.5', 'size' => 0.5, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_0.8', 'size' => 0.8, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_1',  'size' => 1, 'num' => 1000, 'is_rmb' => 1],
                ['alias_name' => 'yaoyiyao_2', 'size' => 0, 'num' => 1000, 'is_rmb' => 0],
                ['alias_name' => 'yaoyiyao_100', 'size' => 0, 'num' => 1000, 'is_rmb' => 0],
            ]
        ]
    ]
];
