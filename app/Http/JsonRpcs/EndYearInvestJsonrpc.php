<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use Illuminate\Pagination\Paginator;

class EndYearInvestJsonrpc extends JsonRpc
{

    /**
     * 奖品列表
     *
     * @JsonRpcMethod
     */
    public function endYearAwardList() {
        $list = static::getAwardNotice();
        shuffle($list);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ];
    }

    //奖品公告信息
    public static function getAwardNotice()
    {
        /*
         投资500,000元 Apple Macbook pro
        投资400,000元 Mavic Pro 4K航拍无人机
        投资320,000元 Apple iPad Pro
        投资250,000元 小米Air 笔记本电脑
        投资170,000元  小米Mix2手机
        投资120,000元  小米Note3手机
        恭喜156******88累计出借50000元获得Apple iPad Pro

        134、135、136、137、138、139、150、151、152、158、159；
        157、182、187
         130、131、132、155、156；
        185、186
        133、153；
        180、189
         */
        $data = array(
            //Apple Macbook pro    20
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '158******59',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '159******94',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '152******55',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '134******72',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '136******67',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '157******19',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '137******81',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '138******38',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '186******67',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '150******41',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '189******81',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '136******44',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '133******39',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '151******39',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '186******80',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '150******16',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '157******84',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '155******52',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '153******47',),
            array ('award_name' => 'Apple Macbook pro','total_amount' => '500,000','phone' => '158******67',),
            //Mavic Pro 4K航拍无人机  20
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '189******65',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '151******12',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '137******58',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '180******80',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '131******74',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '158******83',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '151******86',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '152******67',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '139******34',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '187******65',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '130******13',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '157******76',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '182******12',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '151******76',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '185******41',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '132******23',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '180******89',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '150******26',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '158******27',),
            array ('award_name' => 'Mavic Pro 4K航拍无人机','total_amount' => '400,000','phone' => '152******35',),
            //Apple iPad Pro  20
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '153******86',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '159******62',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '185******75',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '157******64',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '186******83',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '139******36',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '182******43',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '139******19',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '158******63',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '131******66',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '180******81',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '134******95',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '155******31',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '135******50',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '136******93',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '134******78',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '157******42',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '180******80',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '150******23',),
            array ('award_name' => 'Apple iPad Pro','total_amount' => '320,000','phone' => '155******50',),
            //小米Air 笔记本电脑  30
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '139******53',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '131******67',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '159******63',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '137******13',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '185******70',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '133******28',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '155******31',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '134******60',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '137******55',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '139******98',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '153******29',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '136******40',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '139******65',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '130******79',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '189******51',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '185******91',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '159******33',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '132******25',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '136******57',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '136******73',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '151******50',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '159******13',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '151******58',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '136******75',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '150******80',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '186******58',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '150******86',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '155******95',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '159******84',),
            array ('award_name' => '小米Air 笔记本电脑','total_amount' => '250,000','phone' => '159******89',),
            //小米Mix2手机  30
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '135******30',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '150******91',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '185******69',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '189******25',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '187******16',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '150******88',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '157******91',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '138******20',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '134******72',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '130******39',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '139******55',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '137******93',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '187******19',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '157******37',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '150******76',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '157******58',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '150******62',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '132******22',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '150******89',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '158******15',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '157******81',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '132******28',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '182******12',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '152******91',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '135******32',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '153******41',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '187******45',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '180******58',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '130******66',),
            array ('award_name' => '小米Mix2手机',  'total_amount' => '170,000','phone' => '189******85',),
            //小米Note3手机 80个
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '158******29',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '153******85',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '135******31',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '186******53',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '186******44',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '189******70',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '130******92',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '130******63',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '151******18',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '136******31',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '131******45',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '133******14',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '138******81',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '151******83',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '153******16',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '150******50',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '189******23',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '186******37',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '153******81',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '132******23',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '132******33',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '135******34',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '132******24',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '159******64',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '158******73',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '132******77',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '182******20',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '151******53',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '159******26',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '130******69',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '156******53',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '138******40',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '180******63',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '132******76',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '185******98',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '133******95',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '136******15',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '133******27',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '152******89',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '135******31',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '150******31',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '135******97',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '158******34',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '185******83',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '138******59',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '182******68',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '186******95',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '152******33',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '130******83',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '133******90',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '185******14',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '133******83',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '132******67',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '135******65',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '151******18',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '186******95',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '155******44',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '157******76',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '135******85',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '131******38',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '133******75',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '153******58',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '139******71',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '159******54',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '150******96',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '139******72',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '159******70',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '180******41',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '186******79',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '132******88',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '180******27',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '155******89',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '131******75',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '150******12',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '186******29',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '153******87',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '134******47',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '153******29',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '157******35',),
            array ('award_name' => '小米Note3手机',  'total_amount' => '120,000','phone' => '185******11',),
        );

        return $data;
    }
}
