<?php
/**
 * 允许访问的顶级域名列表
 */
// $whiteListDomains = [
//     '/\.(waimaishop\.com)$/',
//     '/(localhost)$/',
//     '/(localhost:[0-9]+)$/',
// ];

// /**
//  * 处理CSRF请求
//  */
// if (isset($_SERVER['HTTP_ORIGIN'])) {
//     //跨域请求Ajax请求，多个直接加数组值

//     $u = parse_url($_SERVER['HTTP_ORIGIN']);
//     foreach ($whiteListDomains as $match) {
//         if (1 === preg_match($match, @$u['host'])) {
//             header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
//             header('Access-Control-Allow-Methods: GET,POST');
//             header('Access-Control-Allow-Headers: Source,Sign,Sign-Time,Appid,Version,Domain');
//             // header('Access-Control-Allow-Headers: *');
//             header('Access-Control-Allow-Credentials: true');
//             header('Access-Control-Max-Age: 3600');
//             break;
//         }
//     }
//     unset($u, $match);

//     // 如果是options请求， 不需要返回响应， 有头部就可以了。
//     if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
//         exit;
//     }
// }
// 判断SCHEME
if (!function_exists('is_https')) {
    function is_https()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }

        return false;
    }
}

// 注册自定义自动加载
function auto_model_class($class_b)
{
    $class = strtolower($class_b);
    if (isset($GLOBALS[$class])) {
        return;
    }
    $paths = array('core/', 'models/', 'bll/', 'libraries/', 'libraries/pages/', 'models/custom/');
    foreach ($paths as $path) {
        $tmp_file = $path . $class;
        if (is_file(APPPATH . $tmp_file . EXT)) {
            require_once APPPATH . $tmp_file . EXT;
            $GLOBALS[$class] = true;
            return;
        }
        $tmp_file = LIBS_PATH . $path . $class_b;
        if (is_file($tmp_file . EXT)) {
            require_once $tmp_file . EXT;
            $GLOBALS[$class] = true;
            return;
        }
    }
}

// Register custom autoload
spl_autoload_register('auto_model_class');

// The URL SCHEME
define('URL_SCHEME', is_https() ? 'https://' : 'http://');
define('WS_SCHEME', is_https() ? 'wss://' : 'ws://');
// 预处理安全码
define('SECRET_KEY', '01802231ffb74aebe6953bee2af58440');

// The PHP file extension
define('EXT', '.php');

// Define system path
define('ROOT', dirname(__FILE__) . '/'); // 根路径
define('LIBS_PATH', ROOT . 'libs/'); // 公用文件路径
define('CI_PATH', ROOT . 'system/'); // CI系统文件路径
define('STATIC_PATH', ROOT . 'static/'); // 静态文件路径
define('UPLOAD_PATH', ROOT . 'uploads/'); // 上传文件的目录

// Database config
define('DB_PREFIX', 'wd_'); // 表前缀
define('DB_PREFIX_YD', 'dbp_'); // 预定义表前缀，非AR操作的表前缀替换
define('DB_DRIVER', 'mysqli');

!defined('IS_CLI') && define('IS_CLI', php_sapi_name() == 'cli');

if (IS_CLI && file_exists(ROOT . '/env.php')) {
    //cli模式下读配置
    require_once ROOT . '/env.php';
} else {
    define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');
}
// Load custom config
require_once 'config.' . ENVIRONMENT . '.php';
