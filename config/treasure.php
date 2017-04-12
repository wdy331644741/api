<?php
return [
    'alias_name' => 'treasure',
    'start_time' => '2017-03-15 00:00:00',
    'copper' => [
        'min' => 20000
    ],
    'silver' => [
        'min' => 60000
    ],
    'gold' => [
        'min' => 110000
    ],
    'config' => [
        'copper'=> [
            ['alias_name' => 'treasure_0.1', 'size' => 0.1, 'num' => 0,'weight' => 47.8, 'is_notLimit' => 1],
            ['alias_name' => 'treasure_0.2', 'size' => 0.2, 'num' => 500,'weight' => 30, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_0.3', 'size' => 0.3, 'num' => 200,'weight' => 12, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_0.5', 'size' => 0.5, 'num' => 100,'weight' => 6, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_0.8', 'size' => 0.8, 'num' => 50,'weight' => 3, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_1',  'size' => 1, 'num' => 20,'weight' => 1.2, 'is_notLimit' => 0],
        ],
        'silver'=> [
            ['alias_name' => 'treasure_0.2', 'size' => 0.2, 'num' => 0,'weight' => 56.5, 'is_notLimit' => 1],
            ['alias_name' => 'treasure_2', 'size' => 2, 'num' => 500,'weight' => 25, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_3', 'size' => 3, 'num' => 100,'weight' => 10, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_5', 'size' => 5, 'num' => 50,'weight' => 5, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_6', 'size' => 6, 'num' => 10,'weight' => 2.5, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_7',  'size' => 7, 'num' => 10,'weight' => 1, 'is_notLimit' => 0],
        ],
        'gold'=> [
            ['alias_name' => 'treasure_0.3', 'size' => 0.3, 'num' => 0,'weight' => 66, 'is_notLimit' => 1],
            ['alias_name' => 'treasure_10', 'size' => 10, 'num' => 100,'weight' => 20, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_20', 'size' => 20, 'num' => 50,'weight' => 10, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_30', 'size' => 30, 'num' => 10,'weight' => 2, 'is_notLimit' => 0],
            ['alias_name' => 'treasure_50', 'size' => 50, 'num' => 10,'weight' => 2, 'is_notLimit' => 0],
        ]
    ]
];
