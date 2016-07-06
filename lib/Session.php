<?php
namespace Lib;
/**
 * Class session
 * @package Lib
 * 获取$_SESSION
 * Lib\Session::get();
 * 设置$_SESSION
 * Lib\Session::set(null,['name' => 'liuqi' , 'mobile' => '18811176547' , 'address' => ['company' => '海航' , 'home' => '霍营']]);
 * 删除$_SESSION某一项,注意这里只会把置变为NULL,而不会undefined.  (获取 设置 同样可以这样指定键名)
 * Lib\Session::del('address.company');
 */
class Session
{
    static private $group; //session数据组名

    public function constructor() {

    }

    static public function get($key = '')
    {
        return self::_session('get', $key);
    }

    static public function set($key = '', $val)
    {
        return self::_session('set', $key, $val);

    }

    static public function del($key = '')
    {
        return self::_session('del', $key);
    }

    static public function selectSessionGroup($group = '')
    {
        if ($group) {
            self::$group = $group;
        } else {
            $baseHost = env('APP_URL', 'wanglibao.com') ;
            $host = parse_url($baseHost, PHP_URL_HOST);
            if (!$host) {
                die('未定义 BASE_HOST,或未配置BASE_HOST');
            }
            self::$group = $host;
        }
    }

    static private function _session($action, $key = '', $val = '')
    {
        if(!self::$group) {
            self::selectSessionGroup();
        }

        session_start();
        $data = &$_SESSION[self::$group];
        session_write_close();
        
        if ($key) {
            $deepKeys = explode('.', $key);
            foreach ($deepKeys as $deepKey) {
                if (isset($data[$deepKey])) {
                    $data =  &$data[$deepKey];
                } else {
                    if ($action == 'set') {
                        $data[$deepKey] = null;
                        $data = &$data[$deepKey];
                    } else {
                        return null;
                    }
                }
            }
        }
        switch ($action) {
            case 'get' :
                return $data;
            case 'set':
                $data = $val;
                return true;
            case 'del':
                $data = null;
                return true;
        }

    }

}