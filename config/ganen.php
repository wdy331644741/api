<?php

return [
    'key' => 'ganen',
    'fresh_time' => 0*3600,
    'buy_number' => 500,
    'probability' => [
        'gan' => 15,
        'en' => 15,
        'da' => 30,
        'hui' => 15,
        'kui' => 25
    ],
    'awards' => [
        //['alias_name' => 'ganen_20', 'size' => 20, 'num' => 20, 'is_rmb' => 1],
        ['alias_name' => 'ganen_10', 'size' => 10, 'num' => 100, 'is_rmb' => 1],
        ['alias_name' => 'ganen_5', 'size' => 5, 'num' => 200, 'is_rmb' => 1],
        ['alias_name' => 'ganen_2', 'size' => 2, 'num' => 500, 'is_rmb' => 1],
        ['alias_name' => 'ganen_1', 'size' => 1, 'num' => 1000, 'is_rmb' => 1],
        ['alias_name' => 'ganen_unlimited_2', 'size' => 2, 'num' => 200, 'is_rmb' => 0],
        ['alias_name' => 'ganen_unlimited_100', 'size' => 100, 'num' => 200, 'is_rmb' => 0],
    ],
    'max' => 1.4,  // 按概率分配时最大浮动范围
    'min' => 0.6,  // 按概率分配时最小浮动范围
    'phone_prefix_list' => [189, 188, 187, 186, 183, 182, 181, 180, 177, 176, 159, 158, 156, 155, 153, 152, 151,150, 139, 138, 137, 136, 135, 134, 132, 131, 130 ],
    'fake_user' => [
        ['award_name' => '200元现金', 'number' => 1],
        ['award_name' => '100元现金', 'number' => 3],
        ['award_name' => '50元现金', 'number' => 6],
    ]
];
