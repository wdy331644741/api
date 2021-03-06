<?php
/**
 * Created by IntelliJ IDEA.
 * User: neil
 * Date: 16/9/5
 * Time: 下午4:35
 */

/*
 *乱序数组 保留数组的 健值不变
 */
function retain_key_shuffle(array &$arr){
  if (!empty($arr)) {
    $key = array_keys($arr);
    shuffle($key);
    foreach ($key as $value) {
      $arr2[$value] = $arr[$value];
    }
    $arr = $arr2;
  }
}

function protectPhone($phone) {
    $prefix = substr($phone, 0, 3);
    $suffix = substr($phone, -4, 4);
    return $prefix . '****' . $suffix;
}

function getMimeTypeByExtension($path)
{

    // our list of mime types
    $mime_types = array(
        "pdf"=>"application/pdf"
        ,"exe"=>"application/octet-stream"
        ,"zip"=>"application/zip"
        ,"docx"=>"application/msword"
        ,"doc"=>"application/msword"
        ,"xls"=>"application/vnd.ms-excel"
        ,"ppt"=>"application/vnd.ms-powerpoint"
        ,"gif"=>"image/gif"
        ,"png"=>"image/png"
        ,"jpeg"=>"image/jpg"
        ,"jpg"=>"image/jpg"
        ,"mp3"=>"audio/mpeg"
        ,"wav"=>"audio/x-wav"
        ,"mpeg"=>"video/mpeg"
        ,"mpg"=>"video/mpeg"
        ,"mpe"=>"video/mpeg"
        ,"mov"=>"video/quicktime"
        ,"avi"=>"video/x-msvideo"
        ,"3gp"=>"video/3gpp"
        ,"css"=>"text/css"
        ,"jsc"=>"application/javascript"
        ,"js"=>"application/javascript"
        ,"php"=>"text/html"
        ,"htm"=>"text/html"
        ,"html"=>"text/html"
    );
    $fileArr = explode('.',$path);
    $extension = strtolower(end($fileArr));
    return $mime_types[$extension] ? $mime_types[$extension] : 'application/octet-stream';
}

function getAgent($item = null)
{
    $header_arr = getallheaders();
    $agent = isset($header_arr['User-Agent']) ? $header_arr['User-Agent'] : '';

    //匹配系统
    $pattern = "/Android|iPhone|iPad|wlbapp|MicroMessenger/imx";
    preg_match_all($pattern, $agent, $result, PREG_PATTERN_ORDER);

    //大小写转换
    $platResult = array_map(function ($val) {
        return strtoupper($val);
    }, $result[0]);


    if (in_array_mul(array('IPHONE', 'IPAD'), $platResult)) {
        //ios
        if (in_array('WLBAPP', $platResult)) {
            $platform = "IOS";
        } else {
            $platform = "H5";
        }
    } elseif (in_array('ANDROID', $platResult)) {
        //ios
        if (in_array('WLBAPP', $platResult)) {
            $platform = "ANDROID";
        } else {
            $platform = "H5";
        }
    } else {
        $platform = "PC";
    }

    $result = array("platform" => $platform);

    if (!empty($item) && isset($result[$item])) {
        return $result[$item];
    }
    return $result;
}
/**
 * 获取输入参数 支持过滤和默认值
 * 文档参考:http://document.thinkphp.cn/manual_3_2.html#input_var
 * 使用方法:
 * <code>
 * I('id',0); 获取id参数 自动判断get或者post
 * I('post.name','','htmlspecialchars'); 获取$_POST['name']
 * I('get.'); 获取$_GET
 * </code>
 *
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @param mixed $datas 要获取的额外数据源
 *
 * @return mixed
 */
function I($name, $default = '', $filter = null, $datas = null)
{
    static $_PUT = null;
    if (strpos($name, '/')) {
        // 指定修饰符
        list($name, $type) = explode('/', $name, 2);
    } else {
        $type = 's';
    }

    if (strpos($name, '.')) {
        // 指定参数来源
        list($method, $name) = explode('.', $name, 2);
    } else {
        // 默认为自动判断
        $method = 'param';
    }
    switch (strtolower($method)) {
        case 'get':
            $input = &$_GET;
            break;
        case 'post':
            $input = &$_POST;
            break;
        case 'put':
            if (is_null($_PUT)) {
                parse_str(file_get_contents('php://input'), $_PUT);
            }
            $input = $_PUT;
            break;
        case 'param':
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input = $_POST;
                    break;
                case 'PUT':
                    if (is_null($_PUT)) {
                        parse_str(file_get_contents('php://input'), $_PUT);
                    }
                    $input = $_PUT;
                    break;
                default:
                    $input = $_GET;
            }
            break;
        case 'path':
            $input = array();
            if (!empty($_SERVER['PATH_INFO'])) {
                $depr = '/';
                $input = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
            }
            break;
        case 'request':
            $input = &$_REQUEST;
            break;
        case 'session':
            $input = &$_SESSION;
            break;
        case 'cookie':
            $input = &$_COOKIE;
            break;
        case 'server':
            $input = &$_SERVER;
            break;
        case 'globals':
            $input = &$GLOBALS;
            break;
        case 'data':
            $input = &$datas;
            break;
        default:
            return null;
    }
    if ('' == $name) {
        // 获取全部变量
        $data = $input;
        $filters = isset($filter) ? $filter : 'htmlspecialchars';
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            }
            foreach ($filters as $filter) {
                $data = array_map_recursive($filter, $data); // 参数过滤
            }
        }
    } elseif (isset($input[$name])) {
        // 取值操作
        $data = $input[$name];
        $filters = isset($filter) ? $filter : 'htmlspecialchars';
        if ($filters) {
            if (is_string($filters)) {
                if (0 === strpos($filters, '/')) {
                    if (1 !== preg_match($filters, (string)$data)) {
                        // 支持正则验证
                        return isset($default) ? $default : null;
                    }
                } else {
                    $filters = explode(',', $filters);
                }
            } elseif (is_int($filters)) {
                $filters = array($filters);
            }
            if (is_array($filters)) {
                foreach ($filters as $filter) {
                    if (function_exists($filter)) {
                        $data = is_array($data) ? array_map_recursive($filter, $data) : $filter($data); // 参数过滤
                    } else {
                        $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                        if (false === $data) {
                            return isset($default) ? $default : null;
                        }
                    }
                }
            }
        }
        if (!empty($type)) {
            switch (strtolower($type)) {
                case 'a':    // 数组
                    $data = (array)$data;
                    break;
                case 'd':    // 数字
                    $data = (int)$data;
                    break;
                case 'f':    // 浮点
                    $data = (float)$data;
                    break;
                case 'b':    // 布尔
                    $data = (boolean)$data;
                    break;
                case 's':// 字符串
                default:
                    $data = (string)$data;

            }
        }
    } else {
        // 变量默认值
        $data = isset($default) ? $default : null;
    }
    is_array($data) && array_walk_recursive($data, 'think_filter');
    return $data;
}
if (!function_exists('in_array_mul')) {

    //在haystack_arr 查找是否存在needle_arr中的某个值
    function in_array_mul($needle_arr, $haystack_arr)
    {
        return count(array_intersect($needle_arr, $haystack_arr));
    }
}
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = null;//php7 版本强类型，初始赋值为字符串会报错
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}


if(!function_exists('msectime')){
    //毫秒时间戳(格式化)
    function msectime() {
        list($tmp1, $tmp2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
    }
}

if(!function_exists('convertUrlQuery')){
    //拼接url后边参数
    function convertUrlQuery($url){
        $check = strpos($url, '?');
        if($check !== false) {
            if(substr($url, $check+1) == '') {
                $new_url = $url;
            } else {
                $new_url = $url.'&';
            }
        } else {
            $new_url = $url.'?';
        }
        return $new_url;
    }
}

if (!function_exists('authcode')){
    /**
     * $string 明文或密文
     * $operation 加密ENCODE或解密DECODE
     * $key 密钥
     * $expiry 密钥有效期
     */
    function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        $ckey_length = 4;
        $key = md5($key ? $key : "wlbmiyao");
        // 密匙a会参与加解密
        $keya = md5(substr($key, 0, 16));
        // 密匙b会用来做数据完整性验证
        $keyb = md5(substr($key, 16, 16));
        // 密匙c用于变化生成的密文
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
        // 参与运算的密匙
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        // 产生密匙簿
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上并不会增加密文的强度
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 核心加解密部分
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if($operation == 'DECODE') {
            // 验证数据有效性，请看未加密明文的格式
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }

}