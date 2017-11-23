<?php
return [
    'alias_name' => 'networkdramadzp',
    'draw_max_number' => 2,
    'drew_user_key' => 'networkdramadzp_drew_user',//用户剩余次数key
    'drew_total_key' => 'networkdramadzp_drew_total',//总共领取次数key
    'awards' => [
//        ['name' => '50元红包', 'alias_name' => 'hongbao50', 'type' => 'activity', 'num' => 10000000, 'weight' => 1250000],
        ['name' => '50元红包', 'alias_name' => 'hongbao50', 'type' => 'activity'],
        ['name' => '100元红包', 'alias_name' => 'hongbao100', 'type' => 'activity'],
        ['name' => '200元红包', 'alias_name' => 'hongbao200', 'type' => 'activity'],
        ['name' => '500元红包', 'alias_name' => 'hongbao500', 'type' => 'activity'],
        ['name' => '1000元红包', 'alias_name' => 'hongbao1000', 'type' => 'activity'],
        ['name' => '5888元体验金', 'alias_name' => 'tiyanjin5888', 'type' => 'activity'],
        ['name' => '8888元体验金', 'alias_name' => 'tiyanjin8888', 'type' => 'activity'],
        ['name' => '谢谢参与', 'alias_name' => 'empty', 'type' => 'empty'],
    ]
];
/*
 *


活动规则：

1. 通过芒果tv观看《猎场》电视剧，扫描剧中网利宝贴片二维码进入网利宝幸运大转盘，每位用户均有一次参与抽奖的机会，分享后可多得一次抽奖机会。

2. 奖品使用规则：
50元直抵红包（投资10000元可用）、
100元直抵红包（投资20000元可用）；
8888元体验金、
5888元体验金；
200元、
500元、
1000元等比例红包（0.5%比例红包，按投资比例折抵，多投多抵红包抵扣上限为200元、500元、1000元）均会发放到您的网利宝账户内，奖品自发放到账户之日起有效期为30天。
 */