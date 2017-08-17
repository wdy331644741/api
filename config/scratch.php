<?php
return [
    'alias_name' => 'scratch',
    'drew_total_key' => 'scratch_drew_total',//用户领取总数key
    'copper' => [
        'key' => 'scratch_copper_num',//用户青铜卡剩余次数key
        'awards' => [
            ['name' => '10元直抵红包', 'alias_name' => 'scratch_hongbao10', 'weight' => 10],
            ['name' => '20元直抵红包', 'alias_name' => 'scratch_hongbao20', 'weight' => 10],
            ['name' => '1.5%全周期加息劵', 'alias_name' => 'scratch_jiaxiquan1.5', 'weight' => 10],
        ]
    ],
    'silver' => [
        'key' => 'scratch_silver_num',//用户白银卡剩余次数key
        'awards' => [
            ['name' => '50元直抵红包', 'alias_name' => 'scratch_hongbao50', 'weight' => 10],
            ['name' => '80元直抵红包', 'alias_name' => 'scratch_hongbao80', 'weight' => 10],
            ['name' => '2%全周期加息劵', 'alias_name' => 'scratch_jiaxiquan2', 'weight' => 10],
        ]
    ],
    'gold' => [
        'key' => 'scratch_gold_num',//用户黄金卡剩余次数key
        'awards' => [
            ['name' => '100元直抵红包', 'alias_name' => 'scratch_hongbao100', 'weight' => 10],
            ['name' => '150元直抵红包', 'alias_name' => 'scratch_hongbao150', 'weight' => 10],
            ['name' => '2.5%全周期加息劵', 'alias_name' => 'scratch_jiaxiquan2.5', 'weight' => 10],
        ]
    ],
    'diamonds' => [
        'key' => 'scratch_diamonds_num',//用户钻石卡剩余次数key
        'awards' => [
            ['name' => '260元直抵红包', 'alias_name' => 'scratch_hongbao260', 'weight' => 10],
            ['name' => '300元直抵红包', 'alias_name' => 'scratch_hongbao300', 'weight' => 10],
            ['name' => '3%全周期加息劵', 'alias_name' => 'scratch_jiaxiquan3', 'weight' => 10],
        ]
    ],
];
