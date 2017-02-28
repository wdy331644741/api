<?php
/**
 * Created by IntelliJ IDEA.
 * User: neil
 * Date: 16/9/5
 * Time: 下午4:35
 */

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
        $headers = '';
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