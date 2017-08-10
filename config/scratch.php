<?php
return [
    'alias_name' => 'scratch',
    'drew_total_key' => 'scratch_drew_total',//用户领取总数key
    'copper' => [
        'key' => 'scratch_copper_num',//用户青铜卡剩余次数key
        'awards' => [
            ['name' => '10元现金红包', 'alias_name' => 'scratch_xianjin10', 'type' => 'rmb', 'size' => 10, 'weight' => 10],
            ['name' => '20元现金红包', 'alias_name' => 'scratch_xianjin20', 'type' => 'rmb', 'size' => 20, 'weight' => 10],
            ['name' => '1.5%全周期加息劵', 'alias_name' => 'scratch_jiaxiquan1.5', 'type' => 'activity', 'weight' => 10],
        ]
    ],
    'silver' => [
        'key' => 'scratch_silver_num',//用户白银卡剩余次数key
        'awards' => [
            ['name' => '50元现金红包', 'alias_name' => 'scratch_xianjin50', 'type' => 'rmb', 'size' => 50, 'weight' => 10],
            ['name' => '80元现金红包', 'alias_name' => 'scratch_xianjin80', 'type' => 'rmb', 'size' => 80, 'weight' => 10],
            ['name' => '2%全周期加息劵', 'alias_name' => 'scratch_jiaxiquan2', 'type' => 'activity', 'weight' => 10],
        ]
    ],
    'gold' => [
        'key' => 'scratch_gold_num',//用户黄金卡剩余次数key
        'awards' => [
            ['name' => '100元现金红包', 'alias_name' => 'scratch_xianjin100', 'type' => 'rmb', 'size' => 100, 'weight' => 10],
            ['name' => '150元现金红包', 'alias_name' => 'scratch_xianjin150', 'type' => 'rmb', 'size' => 150, 'weight' => 10],
            ['name' => '2.5%全周期加息劵', 'alias_name' => 'scratch_jiaxiquan2.5', 'type' => 'activity', 'weight' => 10],
        ]
    ],
    'diamonds' => [
        'key' => 'scratch_diamonds_num',//用户钻石卡剩余次数key
        'awards' => [
            ['name' => '260元现金红包', 'alias_name' => 'scratch_xianjin260', 'type' => 'rmb', 'size' => 260, 'weight' => 10],
            ['name' => '300元现金红包', 'alias_name' => 'scratch_xianjin300', 'type' => 'rmb', 'size' => 300, 'weight' => 10],
            ['name' => '3%全周期加息劵', 'alias_name' => 'scratch_jiaxiquan3', 'type' => 'activity', 'weight' => 10],
        ]
    ],
];
