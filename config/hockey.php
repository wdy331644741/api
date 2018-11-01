<?php
return [
    //集卡配置
    'card_alias_name'=>"hockey_card",//活动时间别名
    'card_key'=> "hockey_card",
    'user_attr' => [
        'China'=>0,
        'Netherlands'=>0,
        'Argentina'=>0,
        'Australia'=>0,
        'England'=>0,
        'Japan'=>0,
    ],
    'cash_list' => [
        '200元现金奖励','300元现金奖励','500元现金奖励'
    ],
    //竞猜场配置
    'guess_alias_name'=>"hockey_guess",//活动时间别名
    'guess_key'=> "hockey_guess",
    'mail_invest_temp'=> "恭喜您在'曲棍球投资'活动中获得'{{awardname}}'奖励。",
    'mail_invite_temp'=> "",
    'guess_team'=>[
        1=>'中国',
        2=>'荷兰',
        3=>'阿根廷',
        4=>'澳大利亚',
        5=>'英国',
        6=>'日本'
    ]
];
