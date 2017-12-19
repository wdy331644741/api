<?php
return [
    'key' => 'diy_increases',//diy加息券key名
    'default_value'=> '15',//就是1.5%加息券
    'max_num' => 3,
    'source_id' => 40000000,
    'config_list' => [
        1 => ['min' => 10000, 'max' => 69999],
        5 => ['min' => 70000, 'max' => 99999],
        10 => ['min' => 100000, 'max' => 99999999]
    ],
];
