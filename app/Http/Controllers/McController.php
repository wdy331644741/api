<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
//use Illuminate\Support\Facades\Redis;

class McController extends Controller
{
    //
    function postCallback(Request $request){
        $res = $request->all();
        file_put_contents('a.txt',json_encode($res) . PHP_EOL, FILE_APPEND);
        return json_encode(["result" => "ok"]);
    }


    private function connect() {
        $this->redis = new \Redis();
        $this->redis->connect("192.168.10.36",6379);
        return $this->redis;
    }

    public function getSend($tag,$value){
        $this->ip = '192.168.10.36';
        $this->port = 6379;
        $redis =self::connect($this->ip,$this->port);
        $json  =json_encode(["tag" => $tag, "value" => $value]);

       $res =  $redis->LPUSH('msg_queue', $json);
        var_dump($res);
        return json_encode(["result" => "ok"]);
    }

}
