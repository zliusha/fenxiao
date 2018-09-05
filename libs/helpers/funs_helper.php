<?php
/**
 * 公用函数库
 */
if (!function_exists('is_ajax')) {
    /**
     * 判定请求是否为ajax ,判定输入头
     * @return  boolean
     */
    function is_ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest");
    }
}
if (!function_exists('is_post')) {
    // 判定请求是否为post
    function is_post()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            return true;
        } else {
            return false;
        }
    }
}
if (!function_exists('is_get')) {
    // 判定请求时否为get
    function is_get()
    {
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            return true;
        } else {
            return false;
        }
    }
}
if (!function_exists('is_json')) {
    function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}
/**
 * liusha
 * 检测是否微信浏览器（不是很严谨）
 */
function is_weixin()
{
    return (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) ? true : false;
}

/**
 * liusha
 * 检测是否qq浏览器
 * @return bool
 */
function is_qq()
{
    return (strpos($_SERVER['HTTP_USER_AGENT'], 'QQBrowser') !== false) ? true : false;
}
/**
 * 浏览器友好的变量输出
 */
if (!function_exists('pdump')) {
    function pdump($var, $echo = true, $label = null, $strict = true)
    {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo ($output);
            return null;
        } else {
            return $output;
        }
    }
}
//array(
// ['code'] =>
// 0
// ['data'] =>
// array(

// ['country'] =>
// '美国'
// ['country_id'] =>
// 'US'
// ['area'] =>
// ['area_id'] =>
// ['region'] =>
// ['region_id'] =>
// ['city'] =>
// ['city_id'] =>
// ['county'] =>
// ['county_id'] =>
// ['isp'] =>
// ['isp_id'] =>
// ['ip'] =>
// 66.104.77.20
// )
// )
function get_ip_json($ip)
{
    $new_json = new stdClass();
    $new_json->ip = $ip;
    $new_json->address = '未知';
    $opts = array(
        'http' => array(
            'method' => "GET",
            'timeout' => 5),
    );
    $context = stream_context_create($opts);
    $url_ip = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
    $str = @file_get_contents($url_ip, false, $context);
    if (!$str) {
        return $new_json;
    }

    $json = json_decode($str, true);
    if ($json['data']['country'] != '中国') {
        $new_json->address = $json['data']['country'];
    } else {
        $new_json->address = $json['data']['region'] . $json['data']['city'];
    }
    return $new_json;
}
/**
 * 取请求客户端IP
 */
if (!function_exists('get_ip')) {
    function get_ip()
    {
        $cip = (isset($_SERVER['HTTP_CLIENT_IP']) and $_SERVER['HTTP_CLIENT_IP'] != "") ? $_SERVER['HTTP_CLIENT_IP'] : false;
        $rip = (isset($_SERVER['REMOTE_ADDR']) and $_SERVER['REMOTE_ADDR'] != "") ? $_SERVER['REMOTE_ADDR'] : false;
        $fip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and $_SERVER['HTTP_X_FORWARDED_FOR'] != "") ? $_SERVER['HTTP_X_FORWARDED_FOR'] : false;

        if ($cip && $rip) {
            $IP = $cip;
        } elseif ($rip) {
            $IP = $rip;
        } elseif ($cip) {
            $IP = $cip;
        } elseif ($fip) {
            $IP = $fip;
        }

        if (strpos($IP, ',') !== false) {
            $x = explode(',', $IP);
            $IP = end($x);
        }

        if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $IP)) {
            $IP = '0.0.0.0';
        }

        unset($cip);
        unset($rip);
        unset($fip);

        return $IP;
    }
}
/**
 * 淘宝IP库
 */
if (!function_exists('parse_ip')) {
    function parse_ip($ip)
    {
        $new_json = new stdClass();
        $new_json->ip = $ip;
        $new_json->address = '未知';
        $opts = array(
            'http' => array(
                'method' => "GET",
                'timeout' => 5),
        );
        $context = stream_context_create($opts);
        $url_ip = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
        $str = @file_get_contents($url_ip, false, $context);
        if (!$str) {
            return $new_json;
        }

        $json = json_decode($str, true);
        if ($json['data']['country'] != '中国') {
            $new_json->address = $json['data']['country'];
        } else {
            $new_json->address = $json['data']['region'] . $json['data']['city'];
        }
        return $new_json;
    }
}
/**
 * 生成订单编号
 */
if (!function_exists('create_order_number')) {
    function create_order_number($pre = '')
    {
        return $pre . date('ymdHi') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
/**
 * 生成唯一的GUID
 */
if (!function_exists('create_guid')) {
    function create_guid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = chr(123) // "{"
         . substr($charid, 0, 8) . $hyphen
        . substr($charid, 8, 4) . $hyphen
        . substr($charid, 12, 4) . $hyphen
        . substr($charid, 16, 4) . $hyphen
        . substr($charid, 20, 12)
        . chr(125); // "}"
        return $uuid;
    }
}
/**
 * 提取string中的数字
 */
function find_num($str = '')
{
    $str = trim($str);
    if (empty($str)) {return '';}
    $temp = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '.');
    $result = '';
    for ($i = 0; $i < strlen($str); $i++) {
        if (in_array($str[$i], $temp)) {
            $result .= $str[$i];
        }
    }
    return $result;
}
/*
根据子数据中key查找多维组，并返回子数组
 */
function array_find($arrays, $child_key, $child_key_value)
{
    foreach ($arrays as $arr) {
        if (isset($arr[$child_key]) && $arr[$child_key] == $child_key_value) {
            return $arr;
        }

    }
    return false;
}
function array_find_list($arrays, $child_key, $child_key_value)
{
    $list_arr = array();
    if (!empty($arrays)) {
        foreach ($arrays as $arr) {
            if (isset($arr[$child_key]) && $arr[$child_key] == $child_key_value) {
                array_push($list_arr, $arr);
            }

        }
    }
    return $list_arr;
}
function object_find($objs, $param, $value)
{

    if (empty($objs)) {
        return false;
    }
    foreach ($objs as $obj) {
        if (isset($obj->$param) && $obj->$param == $value) {
            return $obj;
        }
    }
    return false;
}
function object_find_list($objs, $param, $value)
{
    $obj_arr = array();
    if (!empty($objs)) {
        foreach ($objs as $obj) {
            if (isset($obj->$param) && $obj->$param == $value) {
                array_push($obj_arr, $obj);
            }
        }
    }
    return $obj_arr;
}
//得到小数点时间
function microtime_float($precision = 3)
{
    return sprintf("%.{$precision}f", microtime(true));
}
//返回以逗号隔开
function sql_where_in($arr, $is_int = true)
{
    if (empty($arr)) {
        return "";
    }

    $str_in = "";
    foreach ($arr as $item) {
        if ($is_int) {
            if ($str_in == "") {
                $str_in .= $item;
            } else {
                $str_in .= ',' . $item;
            }

        } else {
            if ($str_in == "") {
                $str_in .= "'" . $item . "'";
            } else {
                $str_in .= ",'" . $item . "'";
            }

        }
    }
    return $str_in;
}
//返回0至1的小数
function rand_float($min, $max)
{
    $rand = rand($min, $max);
    return (float) ('0.' . $rand);
}
/**
 * 返回某个字段的数组
 * @param  [array] $arr   [查找的数组]
 * @param  string $field [description]
 * @return [array]        [不存在返回空数组]
 */
function array_fields($arr, $field = 'id')
{
    $fields = array();
    if (!empty($arr)) {
        foreach ($arr as $item) {
            if (isset($item[$field])) {
                array_push($fields, $item[$field]);
            }

        }
    }
    return $fields;
}
/**
 * 得到int_key数组的区间value
 * @param  [type] $arr     [description]
 * @param  int $int_key 索引
 * @return mixed         索引值
 */
function rang_get_value($arr, $int_key)
{
    if (empty($arr)) {
        return null;
    }

    krsort($arr);
    foreach ($arr as $k => $v) {
        if ($int_key >= $k) {
            return $v;
        }

    }
    return null;
}
/**
 * 得到int_key数组的区间value
 * @param  [type] $arr     [description]
 * @param  int $int_key 索引
 * @return array         $item
 */
function rang_get_item($arr, $int_key)
{
    if (empty($arr)) {
        return null;
    }

    krsort($arr);
    foreach ($arr as $k => $v) {
        if ($int_key >= $k) {
            $item['key'] = $k;
            $item['value'] = $v;
            return $item;
        }
    }
    return null;
}
/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
if (!function_exists('create_linkstring')) {
    function create_linkstring($para)
    {
        $arg = "";
        while (list($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {$arg = stripslashes($arg);}

        return $arg;
    }
}

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
if (!function_exists('create_linkstring_urlencode')) {
    function create_linkstring_urlencode($para)
    {
        $arg = "";
        while (list($key, $val) = each($para)) {
            $arg .= $key . "=" . urlencode($val) . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {$arg = stripslashes($arg);}

        return $arg;
    }
}
/**
 * 解析URL获取GET参数
 * @param  string $url 传入URL
 * @param  string $key GET参数名
 * @return [type]      结果
 */
if (!function_exists('get_param')) {
    function get_param($url = '', $key = '')
    {
        if (!$url || !$key) {
            return '';
        }

        $url_parts = parse_url($url);

        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $param);
            return @$param[$key];
        } else {
            return false;
        }
    }
}
/**
 * 转换文本类型的Cookie为数组形式
 */
if (!function_exists('parse_cookie')) {
    function parse_cookie($cookietxt)
    {
        $cookies = [];
        $parts = explode('; ', $cookietxt);
        if ($parts) {
            foreach ($parts as $key => $row) {
                $kv = explode('=', $row);
                if (count($kv) == 2) {
                    $cookies[$kv[0]] = $kv[1];
                }
            }
            return $cookies;
        } else {
            return false;
        }
    }
}
/**
 * 自建短网址服务
 */
if (!function_exists('dwz')) {
    function dwz($url)
    {
        return file_get_contents('http://t.wadao.com/shorten.php?longurl=' . urlencode($url));
    }
}
/**
 * 加密
 */
//if (!function_exists('encrypt')) {
//    function encrypt($data, $key)
//    {
//        $prep_code = serialize($data);
//        $key = substr(md5($key), 0, 8);
//
//        $block = mcrypt_get_block_size('des', 'ecb');
//        if (($pad = $block - (strlen($prep_code) % $block)) < $block) {
//            $prep_code .= str_repeat(chr($pad), $pad);
//        }
//
//        $encrypt = mcrypt_encrypt(MCRYPT_DES, $key, $prep_code, MCRYPT_MODE_ECB);
//        return base64_encode($encrypt);
//    }
//}
/**
 * 解密
 */
//if (!function_exists('decrypt')) {
//    function decrypt($str, $key)
//    {
//        $key = substr(md5($key), 0, 8);
//        $str = mcrypt_decrypt(MCRYPT_DES, $key, base64_decode($str), MCRYPT_MODE_ECB);
//        $block = mcrypt_get_block_size('des', 'ecb');
//        $pad = ord($str[strlen($str) - 1]);
//        if ($pad && $pad < $block && preg_match('/' . chr($pad) . '{' . $pad . '}$/', $str)) {
//            $str = substr($str, 0, strlen($str) - $pad);
//        }
//
//        return unserialize($str);
//    }
//}
/**
 *  curl http get请求
 */
if (!function_exists('http_get')) {
    function http_get($url = '')
    {
        if (empty($url)) {
            return false;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
}
/**
 * 递归创建目录
 */
if (!function_exists('mkdirs')) {
    function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || mkdir($dir, $mode)) {
            return true;
        }

        if (!mkdirs(dirname($dir), $mode)) {
            return false;
        }

        return mkdir($dir, $mode);
    }
}
/**
 * 递归删除目录
 */
if (!function_exists('rmdirs')) {
    function rmdirs($dir)
    {
        if (!is_dir($dir) || rmdir($dir)) {
            return true;
        }

        if ($dir_handle = opendir($dir)) {
            while ($filename = readdir($dir_handle)) {
                if ($filename != '.' && $filename != '..') {
                    $subFile = $dir . '/' . $filename;
                }
                is_dir($subFile) ? rmdirs($subFile) : unlink($subFile);
            }
            closedir($dir_handle);
            return rmdir($dir);
        }
    }
}
if (!function_exists('check_arr_params')) {
    /**
     * 检测数据格式
     * @param  array $arr        [description]
     * @param  string $params_str 以逗号隔开
     * @return bool             [description]
     */
    function check_arr_params($arr, $params_str)
    {
        if (empty($arr) || empty($params_str)) {
            return false;
        }

        $params_arr = explode(',', $params_str);
        foreach ($params_arr as $p) {
            if (!isset($arr[$p])) {
                return false;
            }

        }
        return true;
    }
}

if (!function_exists('array_search_list')) {
    /**
     * @param $arrays
     * @param $child_key
     * @param $child_key_value 字符串或数组
     * @return array
     */
    function array_search_list($arrays, $child_key, $child_key_value)
    {
        $list_arr = array();
        if (!empty($arrays)) {
            foreach ($arrays as $arr) {
                if (is_array($child_key_value)) {
                    if (isset($arr[$child_key]) && in_array($arr[$child_key], $child_key_value)) {
                        array_push($list_arr, $arr);
                    }
                } else {
                    if (isset($arr[$child_key]) && $arr[$child_key] == $child_key_value) {
                        array_push($list_arr, $arr);
                    }
                }
            }
        }
        return $list_arr;
    }
}

function strip_emoji($str)
{
    $str = preg_replace_callback(
        '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);

    return $str;
}

if (!function_exists('array_sort')) {
    /**
     * @param $arr   排序二维数组
     * @param $keys  排序的键
     * @param string $type asc desc
     * @return array
     */
    function array_sort($arr, $keys, $type = 'asc')
    {

        if (!is_array($arr)) {
            return false;
        }

        $keys_value = $_array = array();
        foreach ($arr as $k => $v) {
            if (is_object($v)) {
                $keys_value[$k] = $v->$keys;
            } else {
                $keys_value[$k] = $v[$keys];
            }

        }
        if ($type == 'asc') {
            asort($keys_value);
        } else {
            arsort($keys_value);
        }

        reset($keys_value);

        foreach ($keys_value as $k => $v) {
            $_array[] = $arr[$k];
        }

        return $_array;
    }
}

if (!function_exists('filter_url_query')) {
    /**
     * 过滤url特定的参数
     * @param string $url
     * @param string $query_str
     * @return string
     */
    function filter_url_query($url = '', $query_str = '')
    {
        $url_parts = parse_url($url);
        $query = "";
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $param);
            if (!empty($param)) {
                foreach ($param as $key => $value) {
                    if ($key == $query_str) {
                        continue;
                    }

                    $query .= "{$key}={$value}&";
                }
            }
        }
        $url = $url_parts['scheme'] . "://" . $url_parts['host'] . $url_parts['path'] . '?' . trim($query, "&");
        return $url;
    }
}
// 对象转为数组
if (!function_exists('object_to_array')) {
    function object_to_array($obj)
    {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;

        foreach ($_arr as $key => $val) {
            $val = (is_array($val)) || is_object($val) ? object_to_array($val) : $val;
            $arr[$key] = $val;
        }

        return $arr;
    }
}

if (!function_exists('is_curr_time_section')) {
    /**
     * 判断当前时间是否在某个时间段
     * @param string $start_time_str
     * @param string $end_time_str
     * @return bool
     */
    function is_curr_time_section($start_time_str = '', $end_time_str = '')
    {
        $check_day_str = date('Y-m-d ', time());
        $time_start = strtotime($check_day_str . " " . $start_time_str);
        $time_end = strtotime($check_day_str . " " . $end_time_str);
        $curr_time = time();
        if ($curr_time >= $time_start && $curr_time <= $time_end) {
            return true;
        }
        return false;
    }
}

if (!function_exists('export_csv')) {
    function export_csv($data, $prefix = '')
    {
        $string = "";
        foreach ($data as $key => $value) {
            foreach ($value as $k => $val) {
                $value[$k] = iconv('utf-8', 'gb2312', $value[$k]);
            }

            $string .= implode(",", $value) . "\n"; //用英文逗号分开
        }
        $filename = $prefix . date('Ymd') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $string;
    }
}

// 获得两个坐标之间的直线距离（辅助函数）
if (!function_exists('distance_rad')) {
    function distance_rad($d)
    {
        return $d * 3.1415926535898 / 180.0;
    }
}
// 获得两个坐标之间的直线距离（单位：千米）
if (!function_exists('get_distance')) {
    function get_distance($lat1, $lng1, $lat2, $lng2)
    {
        $EARTH_RADIUS = 6378.137;
        $radLat1 = distance_rad($lat1);
        $radLat2 = distance_rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = distance_rad($lng1) - distance_rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) +
            cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * $EARTH_RADIUS;
        $s = round($s * 10000) / 10000;
        return $s;
    }
}

if (!function_exists('RecursiveMkdir')) {
    /**
     * Create the directory recursively.
     * @param $path The directory to create, such as, /a/b/c/d/e/
     * @param $mode The mode of the directory to create, such as, 0755, 0777.
     */
    function RecursiveMkdir($path, $mode)
    {
        if (!file_exists($path)) {
            // The file is not exist.
            RecursiveMkdir(dirname($path), $mode); // Call itself.
            if (mkdir($path, $mode)) {
                // Call mkdir() to create the last directory, and the result is true.
                return true;
            } else {
                // Call mkdir() to create the last directory, and the result is false.
                return false;
            }
        } else {
            // The file is already exist.
            return true;
        }
    }
}

if (!function_exists('download_remote_img')) {
    /**
     *
     * @param $pic_path  文件名
     * @param $upload_path 本地目录
     * @param $remote_url  远程文件路径
     */
    function download_remote_img($file_path = '', $local_dir = '', $remote_url = '')
    {
        //如果本地不存在则成七牛拉取到本地
        if (!file_exists($file_path)) {
            if (!is_dir($local_dir)) {
                mkdir($local_dir, 755, true);
            }
            $img = @file_get_contents($remote_url);
            @file_put_contents($file_path, $img);
        }
    }
}

/**
 * 生成流水编号
 */
if (!function_exists('create_serial_number')) {
    function create_serial_number($pre = '', $suf = '')
    {
        return $pre . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT) . $suf;
    }
}

/**
 * 检测数组书否存在对应的键
 */
if (!function_exists('valid_keys_exists')) {

    function valid_keys_exists($keys, $params)
    {
        if (empty($keys) || empty($params)) {
            return false;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $params)) {
                continue;
            } else {
                return false;
            }

        }
        return true;
    }
}
/**
 * 版本号比较,以$version长度为准,当v2不够长时以0补充
 * 返回 -1小于,1大于,0等于
 */
if (!function_exists('compare_version')) {
    function compare_version($version1, $version2, $pattern = '.')
    {
        $v1 = str_replace('v', '', $version1);
        $v2 = str_replace('v', '', $version2);
        $v1arr = explode($pattern, $version1);
        $v2arr = explode($pattern, $version2);
        foreach ($v1arr as $k => $item) {
            $item2 = isset($v2arr[$k]) ? $v2arr[$k] : '0';
            if ($item < $item2) {
                return -1;
            } elseif ($item > $item2) {
                return 1;
            }

        }
        return 0;
    }
}
/**
 * 判断是否是微信APPID
 * 返回 true,false
 */
if (!function_exists('is_wx_appid')) {
    function is_wx_appid($appid)
    {
        if (strpos($appid, 'wx') === 0) {
            return true;
        } else {
            return false;
        }
    }
}
/**
 * 数组转xml
 */
if (!function_exists('array_to_xml')) {
    function array_to_xml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }

        }
        $xml .= "</xml>";
        return $xml;
    }
}
if(!function_exists('power_exists'))
{
    function power_exists($module,$powers)
    {
        //空权限集,代表拥有所有权限
        if(!$powers)
            return true;
        return in_array($module, $powers);

    }
}

//生成邀请码
if (!function_exists('generate_id_code')) {
    function generate_id_code($length = 6)
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0, 25)]
            . strtoupper(dechex(date('m')))
            . date('d')
            . substr(time(), -5)
            . substr(microtime(), 2, 5)
            . sprintf('%02d', rand(0, 99));

        $randMd5 = md5($rand, true);
        $str = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code_str = '';
        for ($i = 0; $i < $length; $i++) {
            $g = ord($randMd5[$i]);
            $code_str .= $str[($g ^ ord($randMd5[$i + 8])) - $g & 0x1F];
        }
        return $code_str;
    }
}
if(!function_exists('get_distance'))
{
    /**
     * 根据地球两点间的经纬度计算距离 默认 km
     * @param $lng1 - 经度值1
     * @param $lat1 - 纬度值1
     * @param $lng2 - 经度值2
     * @param $lat2 - 纬度值2
     * @param float $radius 半径
     * @param int $prec 精度
     * @return float
     */
    function get_distance($lng1, $lat1, $lng2, $lat2, $radius = 6378.137, $prec = 2)
    {
        //将角度转为狐度
        $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * $radius;
        return round($s, $prec);
    }
}
