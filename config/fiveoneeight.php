<?php
return [
    'alias_name' => 'fiveoneeight',
    'draw_number' => 1,
    'drew_daily_key' => 'fiveoneeight_drew_daily',
    'drew_total_key' => 'fiveoneeight_drew_total',
    // 前三次
    'awards_1' => [
        ['name' => '谢谢参与', 'alias_name' => 'empty', 'type' => 'empty', 'num' => 10000000, 'weight' => 0],
        ['name' => '10元红包', 'alias_name' => 'hongbao10', 'type' => 'activity', 'num' => 3000, 'weight' => 0],
        ['name' => '5元红包', 'alias_name' => 'hongbao5', 'type' => 'activity', 'num' => 3000, 'weight' => 0],
        ['name' => '1.5%加息券', 'alias_name' => 'jiaxi15', 'type' => 'activity', 'num' => 3000, 'weight' => 0],
        ['name' => '1元现金', 'alias_name' => 'xianjin1', 'type' => 'rmb', 'size' => 1, 'num' => 3000, 'weight' => 60],
        ['name' => '3元现金', 'alias_name' => 'xianjin3', 'type' => 'rmb', 'size' => 3, 'num' => 500, 'weight' => 40],
        ['name' => '5888元体验金', 'alias_name' => 'tiyanjin5888', 'type' => 'activity', 'num' => 3000, 'weight' => 0],
        ['name' => '8888元体验金', 'alias_name' => 'tiyanjin8888', 'type' => 'activity', 'num' => 3000, 'weight' => 0],
    ],
    // 三次后
    'awards_2' => [
        ['name' => '谢谢参与', 'alias_name' => 'empty', 'type' => 'empty', 'num' => 10000000, 'weight' => 30],
        ['name' => '10元红包', 'alias_name' => 'hongbao10', 'type' => 'activity', 'num' => 3000, 'weight' => 10],
        ['name' => '5元红包', 'alias_name' => 'hongbao5', 'type' => 'activity', 'num' => 3000, 'weight' => 10],
        ['name' => '1.5%加息券', 'alias_name' => 'jiaxi15', 'type' => 'activity', 'num' => 3000, 'weight' => 10],
        ['name' => '1元现金', 'alias_name' => 'xianjin1', 'type' => 'rmb', 'size' => 1, 'num' => 3000, 'weight' => 15],
        ['name' => '3元现金', 'alias_name' => 'xianjin3', 'type' => 'rmb', 'size' => 3, 'num' => 500, 'weight' => 5],
        ['name' => '5888元体验金', 'alias_name' => 'tiyanjin5888', 'type' => 'activity', 'num' => 3000, 'weight' => 10],
        ['name' => '8888元体验金', 'alias_name' => 'tiyanjin8888', 'type' => 'activity', 'num' => 3000, 'weight' => 10],
    ],
];
