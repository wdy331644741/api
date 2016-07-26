<?php

return [
    'rule_child'=>[
        0=>[
            'name'=>'注册时间',
            'model_name'=>'Register',
        ],
        1=>[
            'name'=>'用户渠道',
            'model_name'=>'Channel',
        ],
        2=>[
            'name'=>'是否邀请用户',
            'model_name'=>'Invite',
        ],
        3=>[
            'name'=>'邀请人数',
            'model_name'=>'Invitenum',
        ],
        4=>[
            'name'=>'用户等级',
            'model_name'=>'Userlevel',
        ],
        5=>[
            'name'=>'用户积分',
            'model_name'=>'Usercredit',
        ],
        6=>[
            'name'=>'用户余额',
            'model_name'=>'Balance',
        ],
        7=>[
            'name'=>'投资金额',
            'model_name'=>'Cast',
        ],
        8=>[
            'name'=>'充值金额',
            'model_name'=>'Recharge',
        ],
        9=>[
            'name'=>'回款金额',
            'model_name'=>'Payment',
        ],
    ],
    'activity_type'=>[
        0=>[
            'type_id'=>0,
            'type_name'=>'常规活动'
        ],
        1=>[
            'type_id'=>1,
            'type_name'=>'节日活动'
        ],
        2=>[
            'type_id'=>2,
            'type_name'=>'加急活动'
        ],
        3=>[
            'type_id'=>3,
            'type_name'=>'渠道活动'
        ],
    ],
    'content_type'=>[
        1=>[
            'type_id'=>1,
            'type_name'=>'常规活动'
        ],
        2=>[
            'type_id'=>2,
            'type_name'=>'网站公告'
        ],
        3=>[
            'type_id'=>3,
            'type_name'=>'网利动态'
        ],
    ]

];
