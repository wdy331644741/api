<?php
return [
    'alias_name' => 'dazhuanpan',
    'draw_number' => 1,
    'drew_daily_key' => 'dazhuanpan_drew_daily',//注册次数key
    'drew_user_key' => 'dazhuanpan_drew_user',//用户剩余次数key
    'drew_total_key' => 'dazhuanpan_drew_total',//总共领取次数key
    'awards' => [
        ['name' => '谢谢参与', 'alias_name' => 'empty', 'type' => 'empty', 'num' => 10000000, 'weight' => 30],
        ['name' => '100元红包', 'alias_name' => 'hongbao100', 'type' => 'activity', 'num' => 10000000, 'weight' => 20],
        ['name' => '0.1元现金', 'alias_name' => 'xianjin0.1', 'type' => 'rmb', 'size' => 0.1, 'num' => 2000, 'weight' => 13],
        ['name' => '0.5元现金', 'alias_name' => 'xianjin0.5', 'type' => 'rmb', 'size' => 0.5, 'num' => 400, 'weight' => 12],
        ['name' => '0.8元现金', 'alias_name' => 'xianjin0.8', 'type' => 'rmb', 'size' => 0.8, 'num' => 300, 'weight' => 8],
        ['name' => '1元现金', 'alias_name' => 'xianjin1', 'type' => 'rmb', 'size' => 1, 'num' => 94, 'weight' => 6],
        ['name' => '5元现金', 'alias_name' => 'xianjin5', 'type' => 'rmb', 'size' => 5, 'num' => 60, 'weight' => 4],
        ['name' => '10元现金', 'alias_name' => 'xianjin10', 'type' => 'rmb', 'size' => 10, 'num' => 49, 'weight' => 3],
        ['name' => '安热沙（ANESSA）防晒露60ml', 'alias_name' => 'ANESSA', 'type' => 'shop', 'num' => 5, 'weight' => 2],
        ['name' => '爱马仕（HERMES）尼罗河香水100ml', 'alias_name' => 'HERMES', 'type' => 'shop', 'num' => 2, 'weight' => 1],
        ['name' => '格力KS-0502Db遥控空调扇', 'alias_name' => 'GREE', 'type' => 'shop', 'num' => 2, 'weight' => 1],
    ]
];
