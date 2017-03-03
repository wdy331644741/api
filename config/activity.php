<?php

return [
    'xjdb_global' => [
        'small_num' => 3,  // '单用户小奖最大次数';
        'ip_max_num' => 5,
        'award_msg' => [
            'tyj' => "恭喜您获得{awardName}\n体验金已发送到我的账户—体验金中，请注意查收。",
            'rmb' => "恭喜您获得{awardName}\n奖金已发送到您的余额中，请注意查收。"
        ],
        'cooldown_msg' => [
            "宝箱已经要爆炸了",
            "宝箱已经不堪重负",
            "宝箱已经活不起了",
            "宝箱正在寻找金币",
            "宝箱冷却中",
        ]

    ],
    //寻金夺币
    'xjdb' => [
        'index' => [
            'start_at' => '2016-12-30 15:00:00',
            'end_at' => '2017-01-20 00:00:00',
            'items' => [
                [
                    'start' => 0,
                    'end' => 8,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 4, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 9,
                    'end' => 11,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 52, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 5, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 1, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 12,
                    'end' =>  14,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 10, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 4, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 15,
                    'end' =>  17,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 10, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 4, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 2, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 18,
                    'end' =>  20,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 4, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 21,
                    'end' =>  23,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 4, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 1, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ]
            ]
        ],
        'discover' => [
            'start_at' => '2016-12-30 15:00:00',
            'end_at' => '2017-01-20 00:00:00',
            'items' => [
                [
                    'start' => 0,
                    'end' => 8,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 4, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 1, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 9,
                    'end' => 11,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 2, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 12,
                    'end' =>  14,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 15,
                    'end' =>  17,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 18,
                    'end' =>  20,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 54, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 2, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 21,
                    'end' =>  23,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 52, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 10, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ]
            ]
        ],
        'list' => [
            'start_at' => '2016-12-30 15:00:00',
            'end_at' => '2017-01-20 00:00:00',
            'items' => [
                [
                    'start' => 0,
                    'end' => 8,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 9,
                    'end' => 11,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 1, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 12,
                    'end' =>  14,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 50, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 4, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 15,
                    'end' =>  17,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 52, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 1, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 18,
                    'end' =>  20,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1260, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 52, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 0, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ], [
                    'start' => 21,
                    'end' =>  23,
                    'awards' => [
                        ['money' => 0, 'num' => 0, 'weight' => 69, 'require' => 0, 'isSmall' => 0],
                        ['money' => 0.1, 'num' => 1268, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.2, 'num' => 603, 'weight' => 10, 'require' => 0, 'isSmall' => 1],
                        ['money' => 0.5, 'num' => 52, 'weight' => 1, 'require' => 0, 'isSmall' => 1],
                        ['money' => 10, 'num' => 12, 'weight' => 4, 'require' => 1000, 'isSmall' => 0],
                        ['money' => 50, 'num' => 3, 'weight' => 3, 'require' => 10000, 'isSmall' => 0],
                        ['money' => 100, 'num' => 1, 'weight' => 2, 'require' => 50000, 'isSmall' => 0],
                        ['money' => 200, 'num' => 0, 'weight' => 1,'require' => 100000, 'isSmall' => 0],
                    ]
                ]
            ]
        ]
    ],
    //双十一活动
    'double_eleven' => [
        'key1' => 'double_eleven_default',
        'key2' => 'double_eleven_level1',
        'key3' => 'double_eleven_level2',
        'chance1' => 'double_eleven_chance1',
        'chance2' => 'double_eleven_chance2',
        'baotuan' => 'double_eleven_baotuan',
        'baotuan_probability' => 100,
        'baotuan_level' => [
            ['min'=> 2000,'max'=>4999, 'award'=>'30元红包', 'number' => 5],
            ['min'=> 5000, 'max'=>7999, 'award'=>'80元红包', 'number' => 10],
            ['min'=> 8000, 'max'=>9999, 'award'=>'100元宝贝格子礼品卡', 'number' => 20],
            ['min'=> 10000, 'max'=>11999, 'award'=>'夹克的虾套餐', 'number' => 30],
            ['min'=> 12000, 'max'=>1000000, 'award'=>'百度音乐vip', 'number' => 50],
        ],
        'award_list' => [
            '1111体验金' => 0,
            '天标2.5%加息券' => 1,
            '3月标1%加息券' => 2,
            '10元红包' => 3,
            '50元红包' => 4,
            '百度音乐VIP' => 5,
            '中影票务通电影票' => 6,
            '6月标0.8%加息券' => 7,
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
        ],
        16=>[
            'name'=>'回款期限',
            'model_name'=>'Paymentdate'
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
    'double_eleven_start_time'=>'2016-11-04 16:00:00',//双十一活动开始时间
    'double_eleven_end_time'=>'2016-11-15 00:00:00',//双十一活动结束时间
    'one_yuan'=>[
        'alias_name'=>'one_yuan_investment',
        'investment_num'=>[
            1=>[
                'min'=>'1000',
                'max'=>'2999'
            ],
            4=>[
                'min'=>'3000',
                'max'=>'4999'
            ],
            8=>[
                'min'=>'5000',
                'max'=>'9999'
            ],
            15=>[
                'min'=>'10000',
                'max'=>'49999'
            ],
            80=>[
                'min'=>'50000',
                'max'=>'99999'
            ],
            170=>[
                'min'=>'100000',
                'max'=>'9999999999'
            ]
        ]
    ],
    'money_share_batch'=>10000000, // 红包分享批次id
    'award_batch'=>20000000, // 奖品补发
    'award_batch_other'=>30000000 // 运营成本核算,其它费用
];
