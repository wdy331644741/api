<?php
namespace Lib;
use Redis;

class MqClient
{
    static function send($tag, $value)
    {
        $redis = new Redis();
        $redis->connect(env("REDIS_HOST"), env("REDIS_PORT"));
        $redis->auth(env("REDIS_PASSWORD"));
        $json = json_encode(["tag" => $tag, "value" => $value]);
        $redis->LPUSH('msg_queue', $json);
    }
}
