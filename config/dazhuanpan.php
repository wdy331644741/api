<?php
return [
    'alias_name' => 'longyinhuxiao',
    'draw_number' => 1,
    'drew_user_key' => 'longyinhuxiao_drew_user',//用户剩余次数key
    'drew_total_key' => 'longyinhuxiao_drew_total',//总共领取次数key
    'awards' => [
        ['name' => '100元投资红包', 'alias_name' => 'hongbao100', 'type' => 'activity', 'num' => 10000000, 'weight' => 5000],
        ['name' => '0.1元现金', 'alias_name' => 'xianjin0.1', 'type' => 'rmb', 'size' => 0.1, 'num' => 4000, 'weight' => 2000],
        ['name' => '0.5元现金', 'alias_name' => 'xianjin0.5', 'type' => 'rmb', 'size' => 0.5, 'num' => 500, 'weight' => 1000],
        ['name' => '0.8元现金', 'alias_name' => 'xianjin0.8', 'type' => 'rmb', 'size' => 0.8, 'num' => 300, 'weight' => 1000],
        ['name' => '1元现金', 'alias_name' => 'xianjin1', 'type' => 'rmb', 'size' => 1, 'num' => 84, 'weight' => 800],
        ['name' => '5元现金', 'alias_name' => 'xianjin5', 'type' => 'rmb', 'size' => 5, 'num' => 50, 'weight' => 150],
        ['name' => '10元现金', 'alias_name' => 'xianjin10', 'type' => 'rmb', 'size' => 10, 'num' => 30, 'weight' => 50],
    ]
];