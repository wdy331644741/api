<?php
return [
    'alias_name' => 'perbai',
    'countdown' => 'perbai_countdown',//倒计时开始时间
//    'draw_number' => 1,
    'drew_user_key' => 'perbai_drew_user',//用户剩余次数key
    'drew_total_key' => 'perbai_drew_total',//总共领取次数key
    'awards' => [
        'puzhao'=>['alias_name' => 'puzhao','award_name'=>'阳光普照奖'],
        'yichuidingyin'=>[ 'alias_name' => 'yichuidingyin', 'award_name'=>'一锤定音奖'],
        'yimadangxian'=>[ 'alias_name' => 'yimadangxian', 'award_name'=>'一马当先奖'],
        'zhongjidajiang'=>[ 'alias_name' => 'zhongjidajiang', 'award_name'=>'福星高照-终极大奖'],
    ],
    'message'=> "亲爱的用户，恭喜您抽中{{aliasname}}-{{awardname}}，您的奖品兑换码为：{{code}}，请于当期活动结束后及时联系平台客服人员兑换奖品。温馨提示：确保您在网利宝平台的收货地址准确无误，立即完善收货地址：{{url}}，客服电话：400-858-8066。",
    'node'=> 'perbai4',//push 参数
];

/*
福星高照-终极大奖	Apple iPhone X 256GB
一马当先	        Apple iPad 2018新款 9.7英寸
一锤定音	        Apple Watch Series 3智能手表
阳光普照奖	        200元京东卡
 */