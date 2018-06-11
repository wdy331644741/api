<?php
return [
    'alias_name' => 'world_cup',
    'drew_user_key' => 'world_cup_drew_user',//用户剩余次数key
    'drew_total_key' => 'world_cup_drew_total',//总共领取次数key
    'extra_ball_key' => 'world_cup_extra_ball',
    'date_group' => [
//        ['start'=>'2018-06-07 20:00:00', 'end'=>'2018-06-25 23:59:59'],//测试
        ['start'=>'2018-06-19 20:00:00', 'end'=>'2018-06-25 23:59:59'],
        ['start'=>'2018-06-26 00:00:00', 'end'=>'2018-07-02 23:59:59'],
        ['start'=>'2018-07-03 00:00:00', 'end'=>'2018-07-09 23:59:59'],
        ['start'=>'2018-07-10 00:00:00', 'end'=>'2018-07-16 23:59:59'],
    ],
];