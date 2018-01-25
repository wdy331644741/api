<?php
return [
    'alias_name' => 'robratecoupon',
    'draw_number' => 1,
    'drew_user_key' => 'robratecoupon_drew_user',//用户当前加息券数值
    'drew_total_key' => 'robratecoupon_total_user',//用户已兑换
    'max' => 2.9,
    'weight'=> 100,//总权重
    'awards' => [0.1, 0.2, 0.3],
    'limit' => 2.4,
    'rate' => [
        ['min'=>0, 'max'=>1, 'weight'=> 80],
        ['min'=>1, 'max'=>2, 'weight'=> 50],
        ['min'=>2, 'max'=>2.4, 'weight'=> 30],
    ],
];

/*
 *
加息成功，每次加息力度为0.1%或0.2%或0.3%，三种加息力度出现率一样
用户达到1%之前，加息成功率为80%
用户在1%-2%之间，加息成功率为50%
用户在2%-2.5%加息，加息成功率为30%
当加息超过2.4%后，只允许再发生一次加息，该次加息后，加息助力成功率为0
 */