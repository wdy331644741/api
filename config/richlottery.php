<?php
return [
    'alias_name' => 'rich_lottery',
    'interval' => 5, // 两次抽奖间隔秒数
    'drew_daily_key' => 'richLottery_drew_daily', // 两次抽奖间隔秒数
    'drew_total_key' => 'richLottery_drew_total', // 两次抽奖间隔秒数
    'lists' => [
        [
            'start' => 9,
            'end' => 14,
            'times' => 60*60, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' =>'rich_lottery_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 10],
                ['alias_name' =>'rich_lottery_8.8_in', 'desp' => '8.8%加息券', 'size' => 8.8, 'pro' => 20],
                ['alias_name' =>'rich_lottery_8_re', 'desp' => '8元红包', 'size' => 8.8, 'pro' => 20],
                ['alias_name' =>'rich_lottery_1_in', 'desp' => '1%加息券', 'size' => 1, 'pro' => 10],
                ['alias_name' =>'rich_lottery_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 10],
                ['alias_name' =>'rich_lottery_1.5_in', 'desp' => '1.5加息券', 'size' => 1.5, 'pro' => 10],
                ['alias_name' =>'rich_lottery_888_re', 'desp' => '888元红包', 'size' => 888, 'pro' => 15],
                ['alias_name' =>'thanks', 'desp' => '谢谢参与', 'size' => 0, 'pro' => 5],
            ]
        ], [
            'start' => 14,
            'end' => 20,
            'times' => 60*60, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' =>'rich_lottery_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 5],
                ['alias_name' =>'rich_lottery_8.8_in', 'desp' => '8.8%加息券', 'size' => 8.8, 'pro' => 15],
                ['alias_name' =>'rich_lottery_8_re', 'desp' => '8元红包', 'size' => 8.8, 'pro' => 15],
                ['alias_name' =>'rich_lottery_1_in', 'desp' => '1%加息券', 'size' => 1, 'pro' => 15],
                ['alias_name' =>'rich_lottery_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 20],
                ['alias_name' =>'rich_lottery_1.5_in', 'desp' => '1.5加息券', 'size' => 1.5, 'pro' => 15],
                ['alias_name' =>'rich_lottery_888_re', 'desp' => '888元红包', 'size' => 888, 'pro' => 15],
                // ['alias_name' =>'', 'desp' => '谢谢参与', 'size' => 0, 'pro' => 5],

            ]
        ], [
            'start' => 20,
            'end' =>  9,
            'times' => 60*60, // 活动最长持续时间(秒),到时间后强制结束
            'awards' => [
                ['alias_name' =>'rich_lottery_18888_ex', 'desp' => '18888元体验金', 'size' => 18888, 'pro' => 10],
                ['alias_name' =>'rich_lottery_8.8_in', 'desp' => '8.8%加息券', 'size' => 8.8, 'pro' => 15],
                ['alias_name' =>'rich_lottery_8_re', 'desp' => '8元红包', 'size' => 8.8, 'pro' => 10],
                ['alias_name' =>'rich_lottery_1_in', 'desp' => '1%加息券', 'size' => 1, 'pro' => 20],
                ['alias_name' =>'rich_lottery_88_re', 'desp' => '88元红包',  'size' => 88, 'pro' => 20],
                ['alias_name' =>'rich_lottery_1.5_in', 'desp' => '1.5加息券', 'size' => 1.5, 'pro' => 10],
                ['alias_name' =>'rich_lottery_888_re', 'desp' => '888元红包', 'size' => 888, 'pro' => 10],
                ['alias_name' =>'thanks', 'desp' => '谢谢参与', 'size' => 0, 'pro' => 5],
            ]
        ]
    ]
];
