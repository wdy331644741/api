<?php
return [
    'alias_name' => 'kb_dazhuanpan',
    'draw_number' => 1,
    'drew_user_key' => 'kb_dazhuanpan_drew_user',//用户剩余次数key
    'drew_total_key' => 'kb_dazhuanpan_drew_total',//总共领取次数key
    'awards' => [
        ['name' => '100元投资红包', 'mark'=>'杜海涛的红包','alias_name' => 'hongbao100', 'type' => 'activity', 'num' => -1, 'weight' => 5000],
        ['name' => '0.1元现金红包', 'mark'=>'李维嘉的红包','alias_name' => 'xianjin0.1', 'type' => 'rmb', 'size' => 0.1, 'num' => 5600, 'weight' => 2000],
        ['name' => '0.5元现金红包', 'mark'=>'黑衣人小方的红包','alias_name' => 'xianjin0.5', 'type' => 'rmb', 'size' => 0.5, 'num' => 400, 'weight' => 1000],
        ['name' => '0.8元现金红包', 'mark'=>'吴昕的红包','alias_name' => 'xianjin0.8', 'type' => 'rmb', 'size' => 0.8, 'num' => 300, 'weight' => 1000],
        ['name' => '1元现金红包', 'mark'=>'何炅的红包','alias_name' => 'xianjin1', 'type' => 'rmb', 'size' => 1, 'num' => 200, 'weight' => 800],
        ['name' => '5元现金红包', 'mark'=>'谢娜的红包','alias_name' => 'xianjin5', 'type' => 'rmb', 'size' => 5, 'num' => 60, 'weight' => 150],
        ['name' => '10元现金红包', 'mark'=>'快乐家族的红包','alias_name' => 'xianjin10', 'type' => 'rmb', 'size' => 10, 'num' => 50, 'weight' => 50],
        ['name' => '谢谢参与', 'mark'=>'谢谢参与','alias_name' => 'xiexiecanyu', 'type' => 'empty', 'size' => 0, 'num' => 0, 'weight' => 0],
    ],
];