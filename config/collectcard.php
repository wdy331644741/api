<?php
return [
    'alias_name' => 'collect_card',
    'alias_name_draw' => 'collect_card_draw',
    'draw_number' => 1,
    'drew_user_key' => 'collect_card_drew_user',//用户抽卡剩余次数key
    'drew_total_key' => 'collect_card_drew_total',//总共抽卡领取次数key
    'award_user_key' => 'collect_card_award_user',//用户抽奖剩余次数key
    'award_total_key' => 'collect_card_award_total',//总共抽奖领取次数key
    'day_login_key' => 'collect_card_login',//
    'day_share_key' => 'collect_card_share',
    'card_name' => [
        'liubei'=> '刘备',
        'zhangfei'=> '张飞',
        'huangzhong'=> '黄忠',
        'zhaoyun'=> '赵云',
        'zhugeliang'=>'诸葛亮',
    ],
    //实名送红包, 需要把红包记录到该活动的发奖表中
    'register_award' => ['name'=>'188元新手红包', 'alias_name' => 'advanced_real_name','card_name'=>'liubei',  'type' => 'virtual'],
    'awards' => [
        ['name' => '2%全周期加息券', 'alias_name' => 'jiaxi2', 'card_name'=>'zhangfei', 'type' => 'activity', 'weight' => 30],
        ['name' => '2元直抵红包', 'alias_name' => 'hongbao2', 'card_name'=>'huangzhong', 'type' => 'activity',  'weight' => 20],
        ['name' => '5元直抵红包', 'alias_name' => 'hongbao5', 'card_name'=>'zhaoyun', 'type' => 'activity',  'weight' => 20],
        ['name' => '谢谢参与', 'alias_name' => 'empty', 'card_name'=>'', 'type' => 'empty', 'weight' => 30],
    ],
    'end_award' => ['name' => '翻倍卡牌', 'alias_name' => 'fanbei',  'card_name'=>'zhugeliang', 'type' => 'virtual'],
    'channel' => [
        'haoyouyaoqing1','360jj','360pcss','360ydss','APPStore','APPStorePlus','baidu','baidujj','baidupz','chuizi','fwh','huawei','lenovo','m360','m360jj','mbaidujj','mbaidupz','meizu','oppo','oppofeed','qq','qqplus','qqplus1','qqplus2','samsung','sgqqdh','sogou','sougou1','vivo','wanglibao1','xiaomi','xiaomiplus','ali','qqcpd','gdt','gdt1','sglccpt','360pz','m360pz','fhcpc','jrttcpc','sgpcss','sgydss','sgpz','msgpz','qqcpd1','ggkdg','xlps','fh1','fh2','fh3','fh4','fh5',' 518TYHDpc','518TYHDh5',' LDLT-dx','bkl2018','toutiao1','toutiao2','toutiao3','toutiao4','toutiao5','toutiao6','toutiao7','toutiao8','toutiao9','toutiao10','toutiao11','toutiao12','toutiao13','toutiao14','toutiao15','toutiao16','toutiao17','toutiao18','toutiao19','toutiao20','LDLT','kbjk',
          //  'lc-kolxl',//测试使用
    ],
    'chaopiaoqiang_alisname'=> 'collect_card_chaopiaoqiang',
    'chaopiaoqiang'=> 20,
    'message'=> '恭喜您在“三国集卡”活动中获得“{{awardname}}”奖品，我们将按照您账户内的收货地址陆续邮寄，请尽快前往填写。感谢您的支持！',
];