<?php
return [
    'alias_name' => 'jump',
//    'draw_number' => 1,
    'drew_user_key' => 'jump_drew_user',//用户剩余次数key
    'drew_total_key' => 'jump_drew_total',//总共领取次数key
    'awards' => [//activity
        ['id'=>1, 'name' => '0.5元现金红包', 'alias_name' => 'xianjin0.5', 'type' => 'rmb', 'size'=>0.5],
        ['id'=>2,'name' => '1元现金红包', 'alias_name' => 'xianjin1', 'type' => 'rmb', 'size' => 1],
        ['id'=>3,'name' => '5元直抵红包', 'alias_name' => 'hongbao5', 'type' => 'activity'],
        ['id'=>4,'name' => '1%加息券', 'alias_name' => 'jiaxi1', 'type' => 'activity'],
        ['id'=>5,'name' => '10元直抵红包', 'alias_name' => 'hongbao10', 'type' => 'activity'],
        ['id'=>6,'name' => '爱奇艺会员月卡', 'alias_name' => 'aiqiyi1', 'type' => 'activity'],
        ['id'=>7,'name' => '2元现金红包', 'alias_name' => 'xianjin2', 'type' => 'rmb', 'size' => 2],
        ['id'=>8,'name' => '30元直抵红包', 'alias_name' => 'hongbao30', 'type' => 'activity'],
        ['id'=>9,'name' => '2%加息券', 'alias_name' => 'jiaxi2', 'type' => 'activity'],
        ['id'=>10,'name' => '5元现金红包', 'alias_name' => 'xianjin5', 'type' => 'rmb', 'size' => 5],
        ['id'=>11,'name' => '8.8元现金红包', 'alias_name' => 'xianjin8', 'type' => 'rmb', 'size' => 8.8],
        ['id'=>12,'name' => '爱奇艺会员季卡', 'alias_name' => 'aiqiyi3', 'type' => 'activity'],
        ['id'=>13,'name' => '50元直抵红包', 'alias_name' => 'hongbao50', 'type' => 'activity'],
        ['id'=>14,'name' => '3%加息券', 'alias_name' => 'jiaxi3', 'type' => 'activity'],
        ['id'=>15,'name' => '18.8元现金红包', 'alias_name' => 'xianjin18', 'type' => 'rmb', 'size' => 18.8],
        ['id'=>16,'name' => '100元直抵红包', 'alias_name' => 'hongbao100', 'type' => 'activity'],
        ['id'=>17,'name' => '50JD卡', 'alias_name' => 'jingdongka50', 'type' => 'virtual'],
        ['id'=>18, 'award'=> [
                        ['id'=>18, 'name' => '小米手机S8', 'alias_name' => 'phone', 'type' => 'virtual', 'weight' => 2],
                        ['id'=>18, 'name' => '爱奇艺半年会员卡', 'alias_name' => 'aiqiyi6', 'type' => 'activity', 'weight' => 100],
                    ]
        ],
    ]
];
//（1）0.5元现金红包
//（2）1元现金红包
//（3）5元直抵红包
//（4）1%加息券
//（5）10元直抵红包（无门槛）
//（6）爱奇艺会员月卡
//（7）2元现金
//（8）30元直抵红包（满2000、3个月以上）
//（9）2%加息券（无门槛）
//（10）5元现金
//（11）8.8元现金
//（12）爱奇艺会员季卡
//（13）50直抵红包（满3000、3个月以上）
//（14）3%加息券
//（15）18.8元现金
//（16）100直抵红包（满10000、3个月以上）
//（17）50JD卡
//（18）神秘大奖（小米手机S8、爱奇艺半年会员卡）
