<?php
namespace App\Service;


use App\Models\HdHockeyCard;
use App\Models\HdHockeyCardMsg;
use App\Models\HdHockeyGuess;
use App\Models\HdHockeyGuessConfig;
use App\Models\UserAttribute;
use Config,DB;
class Hockey
{
     
    /**
     * 投资或者邀请好友送卡接口
     *
     * @param int 用户id 
     * @param num 卡数量
     * 
     * @return int
     */
    static function HockeyCardObtain($userId, $amount ) {
        $config = Config::get("hockey");
        $userAttr = isset($config['user_attr']) ? $config['user_attr'] : [];
        $key = isset($config['card_key']) ? $config['card_key'] : '';
        $num = intval($amount/10000);
        $isExist = 0;
        if($userId > 0 && $num > 0 && !empty($key) && !empty($userAttr)){
            $attr = UserAttribute::where(['user_id'=>$userId,'key'=>$key])->first();
            if(isset($attr['string'])){
                $isExist = 1;
                $userAttr = json_decode($attr['string'],1);
            }
            //添加卡
            for($i=1;$i<=$num;$i++){
                $max = array_search(max($userAttr),$userAttr);
                $min = array_search(min($userAttr),$userAttr);
                foreach($userAttr as $k => $item){
                    if($userAttr[$max] == $userAttr[$min]){
                        $userAttr[$k] += 1;
                        break;
                    }else{
                        if($item < $userAttr[$max]){
                            $userAttr[$min] += 1;
                            break;
                        }
                    }
                }
            }
        }
        //添加卡片次数
        if($isExist == 1){
            $attr->string = json_encode($userAttr);
            $attr->increment('number', $num);
            $attr->save();
        }else{
            UserAttribute::create(
                ['user_id' => $userId, 'key' => $key,  'number' => $num, 'string'=>json_encode($userAttr)]
            );
        }
        //添加投资获取卡片接口
        $userInfo = Func::getUserBasicInfo($userId);
        $phone = isset($userInfo['username']) ? trim($userInfo['username']) : '';
        $displayName = substr_replace($phone, '******', 3, 6);
        $msg = "恭喜".$displayName."投资".$amount."元，获取".$num."张卡片";
        $remark['attr'] = json_encode($userAttr);
        $remark['user_info'] = $userInfo;
        $id = HdHockeyCardMsg::insertGetId(['user_id'=>$userId,'msg'=>$msg,'remark'=>json_encode($remark),'type'=>1,'created_at'=>date("Y-m-d H:i:s")]);
        return $id;
    }
    /**
     * 获取应该获取的现金奖励
     *
     * @param $userId 用户id
     *
     * @return int
     */
    static function getHockeyCardExchangeAward($userId){
        $config = Config::get("hockey");
        //获取用户冠军卡已兑换现金的个数
        $already = HdHockeyCard::where(['user_id'=>$userId,'type'=>1,'status'=>1])->count();
        //判断用户应该获取哪一个现金奖励
        if($already <= 0){
            return $config['cash_list'][0];
        }elseif($already == 1){
            return $config['cash_list'][1];
        }elseif($already >= 2){
            return $config['cash_list'][2];
        }
        return '';
    }
    /**
     * 获取当前的日期，下一场倒计时，下一场日期
     *
     * @param $data 后台配置的国家对阵单条数据
     *
     * @return int
     */
    static function getMatchDate(){
        //返回值
        $res = ['next_time'=>0,"next_date"=>0,'match_date'=>'2018-11-17'];
        //获取后台配置普通场
        $data = HdHockeyGuessConfig::where("champion_status",0)->orderBy('match_date', 'asc')->get()->toArray();
        $minDate = isset($data[0]['match_date']) ? $data[0]['match_date'] : "2018-11-17";
        $maxDate = isset($data[count($data)-1]['match_date']) ? $data[count($data)-1]['match_date'] : "2018-11-24";
        //判断当前日期在什么区间
        if(date("Y-m-d H") < $minDate." 14"){//未到配置赛程期间
            $res['next_time'] = strtotime($minDate." 14:00:00") - time();
            $res['next_date'] = date("d",strtotime($minDate));
            $res['match_date'] = $minDate;//默认时间第一天
        }
        if(date("Y-m-d H") >= $minDate." 14" && date("Y-m-d H") < $maxDate." 14"){//在配置赛程期间
            foreach($data as $k => $v){
                if($v['match_date'] == date("Y-m-d")){
                    //判断又没有过去当天的竞猜结束日期
                    if(date("Y-m-d H") < date("Y-m-d 14")){
                        $res['next_time'] = strtotime(date("Y-m-d 14:00:00")) - time();
                        $res['next_date'] = date("d",strtotime(date("Y-m-d")));
                    }else{
                        $res['next_time'] = strtotime($data[$k+1]['match_date']." 14:00:00") - time();
                        $res['next_date'] = date("d",strtotime($data[$k+1]['match_date']));
                    }
                }
            }
            $res['match_date'] = date("Y-m-d");//默认日期当天
        }
        if(date("Y-m-d H") >= $maxDate." 14"){//已过去配置赛程期间
            $res['next_time'] = -1;
            $res['next_date'] = date("d",strtotime($maxDate));
            $res['match_date'] = $maxDate;//默认日期最后一天
        }
        return $res;
    }
    /**
     * 获取国家队对阵信息
     *
     * @param $data 后台配置的国家对阵单条数据
     *
     * @return int
     */
    static function formatHockeyGuessData($data){
        if(!isset($data['id'])){
            return $data;
        }
        //第一场
        self::getHockeyNationalTeam($data,$data['first'],'first');
        //第二场
        self::getHockeyNationalTeam($data,$data['second'],'second');
        //第三场
        self::getHockeyNationalTeam($data,$data['third'],'third');
        return $data;
    }
    /**
     * 获取国家队对阵信息
     *
     * @param $string 字符串（1-2）
     *
     * @return int
     */
    static function getHockeyNationalTeam(&$data,$string,$type){
        $array = explode("-",$string);
        if(is_array($array) && count($array) == 2){
            $config = Config::get("hockey");
            foreach($array as $key => $val){//循环获取每一场对应的国家队
                if($key == 0){
                    $data[$type.'_master'] = $config['guess_team'][$val];
                }
                if($key == 1){
                    $data[$type.'_visiting'] = $config['guess_team'][$val];
                }
            }
        }
    }
    /**
     * 竞猜后台开奖的时候将奖励生成展示给用户
     */
    static function openGuess($openName,$amount){
        if(!empty($openName)){
            //获取场次的中奖列表
            $data = HdHockeyGuess::where('find_name',$openName)->where('status',0)->select("id","user_id","type","match_date","find_name",DB::raw("sum(num) as user_total"))->groupBy("user_id")->get();
            if(isset($data[0]['user_id'])){
                //计算总押注数
                $total = 0;
                foreach($data as $item){//循环计算中奖的总押注数
                    $total += $item['user_total'];
                }
                $avg = round($amount/$total,2);//保留两位小数
                $tmpAmount = 0;
                foreach($data as $value){
                    $sumAmount = round($value['user_total'] * $avg);//用户应得金额
                    if($sumAmount > $amount){//如果大于总奖金，置为总奖金
                        $sumAmount = $amount;
                    }
                    $value->amount = $sumAmount;//修改中奖金额
                    $value->status = 1;//改为中奖
                    $value->save();//保存
                    //判断场次
                    $site = '';
                    if(strpos($value->find_name,"first")){
                        $site = "第一场";
                    }elseif(strpos($value->find_name,"second")){
                        $site = "第二场";
                    }elseif(strpos($value->find_name,"third")){
                        $site = "第三场";
                    }elseif(strpos($value->find_name,"champion")){
                        $site = "冠军场";
                    }
                    //发送站内信
                    $msg = "亲爱的用户，曲棍球竞猜场 ".$value->match_date.$site."已开奖，恭喜您获得现金".$value->amount."元，奖励将于比赛休息日发放至您的网利宝账户，请届时注意查收。客服电话：400-858-8066。";
                    SendMessage::Mail($value->user_id,$msg);
                    $tmpAmount += $value->amount;//发放累计总金额
                    if($tmpAmount >= $amount){//防止发送超出总金额
                        break;
                    }
                }
                return true;
            }
        }
        return false;
    }
}