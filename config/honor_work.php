<?php
return [
    'key' => 'honor_work',
    'rule'=>[
        'share'=>'laodong',//分享给踏实勋章
        'check_in_alias' => 'honor_work_check_in',//记录签到数据
        //'check_invite' => 'honor_work_invite' //邀请注册送勋章
        'check_red' => 'honor_work_red_use',//记录签到数据
    ],
    'red' => [//红包列表
        'red_honor_work_38'=>[
            'name'=>'38',
            'status'=>0//0未领取；1领取；2使用
        ],
        'red_honor_work_118'=>[
            'name'=>'118',
            'status'=>0
        ],
        'red_honor_work_158'=>[
            'name'=>'158',
        'status'=>0
        ],
        'red_honor_work_208'=>[
            'name'=>'208',
            'status'=>0
        ],
        'red_honor_work_328'=>[
            'name'=>'328',
            'status'=>0
        ],
        'red_honor_work_508'=>[
            'name'=>'508',
            'status'=>0
        ],
    ],
    'badge' =>[//奖章列表
        'xianfeng'=>0,//签到1天
        'qinlao'=>0,//签到3天
        'tashi'=>0,//踏实奖章
        'xianjin'=>0,
        'mofan'=>0,
        'aixin'=>0,
        'jingye'=>0,
        'laodong'=>0
    ],
    'welfare'=>[
        'welfare1'=>['condition'=>3,'status'=>0],//福利一，需要满足3个勋章 条件
        'welfare2'=>['condition'=>5,'status'=>0],//福利二
        'welfare3'=>['condition'=>7,'status'=>0],//福利三
        'welfare4'=>['condition'=>8 ,'status'=>0],
    ],
    // 设置渠道黑名单
    'let_blackList' => ['tongdao', 'FJQJ', 'WHDL', 'jffl', 'flt', 'CYGJXJJL', 'htfl', 'ducaishe', 'wdtysw', 'wdtyfl', 'wangdaizhijia', 'gongzhonghao', 'zhijia', 'WLBcps3', 'WLBcps2', 'WLBcps', 'HEGS', 'QY5', 'QY4', 'QY3', 'QY2', 'QY1', 'HHJD', 'QZWL3', 'QZWL2', 'SHLD', 'BJYZ', 'QZWL', 'YD10', 'YD9', 'YD8', 'YD7', 'YD6', 'YD5', 'YD4', 'YD3', 'YD2', 'YD1', 'JG5', 'JG4', 'JG3', 'JG2', 'JG1', 'bdnb22', 'bdnb21', 'bdnb20', 'bdnb19', 'bdnb18', 'bdnb17', 'bdnb16', 'bdnb15', 'bdnb14', 'bdnb13', 'bdnb12', 'bdnb11', 'bdnb10', 'bdnb9', 'bdnb8', 'bdnb7', 'bdnb6', 'bdnb5', 'bdnb4', 'bdnb3', 'bdnb2', 'bdnb1']


];
