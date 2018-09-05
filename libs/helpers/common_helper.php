<?php
/**
 * 项目相关公共函数
 */
//将数组的相对路径转url路径
function convert_picurl_arr($arr, $key)
{

    if (empty($arr) || empty($key)) {
        return $arr;
    }

    foreach ($arr as $k => $v) {
        if (isset($v[$key]) && !empty($v[$key])) {
            if(strpos($arr[$k][$key], 'https://')===0 || strpos($arr[$k][$key], 'http://')===0)
              continue;
            else
              $arr[$k][$key] = UPLOAD_URL . $v[$key];
        }
    }
    return $arr;
}
//将路径转url路径
function conver_picurl($path)
{
    if (empty($path)) {
        return '';
    } else {
        if(strpos($path, 'https://')===0 || strpos($path, 'http://')===0)
          return  $path;
        else
          return UPLOAD_URL . $path;
    }

}
//picarr转url路径
function conver_picarr($arr)
{
    if (empty($arr)) {
        return $arr;
    }

    for ($i = 0; $i < count($arr); $i++) {
        if(strpos($arr[$i], 'https://')===0 || strpos($arr[$i], 'http://')===0)
          continue;
        else
          $arr[$i] = UPLOAD_URL . $arr[$i];
    }
    return $arr;
}
function convert_time($time, $format = 'y-m-d H:i:s')
{
    $n_time = '';
    if (!empty($time) && is_numeric($time)) {
        $n_time = date($format, $time);
    }
    return $n_time;
}
//将状态key转为值 -> 分割路径
function convert_enum($value, $enum_path)
{
    $enum_inc = &inc_config('enum');
    $paths = explode('->', $enum_path);
    $mxied_value = $enum_inc;
    foreach ($paths as $path) {
        $mxied_value = $mxied_value[$path];
    }
    return $mxied_value[$value];
}
//将位状态key转为值 -> 分割路径
function convert_w_enum($value, $enum_path, $default_value = "正常")
{
    if ($value == 0) {
        return $default_value;
    }

    $enum_inc = &inc_config('enum');
    $paths = explode('->', $enum_path);
    $mxied_value = $enum_inc;
    foreach ($paths as $path) {
        $mxied_value = $mxied_value[$path];
    }
    $w_value = "";
    foreach ($mxied_value as $k => $v) {
        if (($value & 1 << $k) > 0) {
            if ($w_value == "") {
                $w_value = $v;
            } else {
                $w_value .= ",{$v}";
            }

        }
    }
    return $w_value;
}
// 将位状态key转为值 -> 分割路径 仅返回最后一位状态
function convert_r_enum($value, $enum_path, $default_value = "正常")
{
    if ($value == 0) {
        return $default_value;
    }

    $enum_inc = &inc_config('enum');
    $paths = explode('->', $enum_path);
    $mxied_value = $enum_inc;
    foreach ($paths as $path) {
        $mxied_value = $mxied_value[$path];
    }
    foreach (array_reverse($mxied_value, true) as $k => $v) {
        if (($value & 1 << $k) > 0) {
            return $v;
        }
    }
    return false;
}
//转为加*字符串
function convert_secret_name($value)
{
    if (empty($value)) {
        return '';
    }

    $len = mb_strlen($value);
    if ($len == 1) {
        return '**';
    } else if ($len == 2) {
        $s = mb_substr($value, 0, 1);
        return $s . '*';
    } else {
        $s = mb_substr($value, 0, 1);

        $e = mb_substr($value, $len - 1, 1);
        $t = str_pad('', ($len - 2) > 3 ? 3 : $len - 2, '*');
        return $s . $t . $e;
    }
}
//将列表转为前端显示列表
function convert_client_list($list, $convert_rule = array(array('type' => 'time', 'field' => 'time')))
{
    if (empty($list)) {
        return $list;
    }

    foreach ($list as $key => $value) {
        foreach ($convert_rule as $ck => $cv) {
            $type = isset($cv['type']) ? $cv['type'] : '';
            //转换时间
            if ($type == 'time' && isset($value[$cv['field']])) {
                $field_value = $value[$cv['field']];
                $format = isset($cv['format']) ? $cv['format'] : 'Y-m-d H:i:s';
                $list[$key][$cv['field']] = convert_time($field_value, $format);
            }

            //枚举转换
            if ($type == 'enum' && isset($value[$cv['field']])) {
                $field_value = $value[$cv['field']];
                $enum_path = $cv['enum_path'];
                $list[$key][$cv['field']] = convert_enum($field_value, $enum_path);
            }
            //位枚举转换
            if ($type == 'w_enum' && isset($value[$cv['field']])) {
                $field_value = $value[$cv['field']];
                $enum_path = $cv['enum_path'];
                $default_value = isset($cv['default_value']) ? $cv['default_value'] : '正常';
                $list[$key][$cv['field']] = convert_w_enum($field_value, $enum_path, $default_value);
            }
            //位枚举转换 仅返回最后一位状态
            if ($type == 'r_enum' && isset($value[$cv['field']])) {
                $field_value = $value[$cv['field']];
                $enum_path = $cv['enum_path'];
                $default_value = isset($cv['default_value']) ? $cv['default_value'] : '正常';
                $list[$key][$cv['field']] = convert_r_enum($field_value, $enum_path, $default_value);
            }
            //图片转换
            if ($type == 'img' && isset($value[$cv['field']])) {
                $field_value = $value[$cv['field']];
                if (!(stripos($field_value, 'https') === 0 || stripos($field_value, 'http') === 0)) {
                    $list[$key][$cv['field']] = conver_picurl($field_value);
                }
            }
            //序列化图片的转换（多图片转化）$cv['prefix'] 是否加前缀
            if ($type == 'serialize_img_arr' && isset($value[$cv['field']])) {
                $field_value = empty($value[$cv['field']]) ? '' : unserialize($value[$cv['field']]);
                $list[$key][$cv['field']] = (isset($cv['prefix']) && $cv['prefix'] == false) ? $field_value : conver_picarr($field_value);
            }
            //加* str
            if ($type == 'secret_name' && isset($value[$cv['field']])) {
                $field_value = $value[$cv['field']];
                $list[$key][$cv['field']] = convert_secret_name($field_value);
            }
            // 价格格式化
            if ($type == 'price' && isset($value[$cv['field']])) {
                $field_value = $value[$cv['field']];
                $list[$key][$cv['field']] = number_format($field_value, 2);
            }
            // 数字格式化
            if ($type == 'round' && isset($value[$cv['field']])) {
                $field_value = $value[$cv['field']];
                $list[$key][$cv['field']] = number_format($field_value, 0);
            }
            // 浮点格式化
            if ($type == 'floatval' && isset($value[$cv['field']])) {
                $field_value = $value[$cv['field']];
                $list[$key][$cv['field']] = floatval($field_value);
            }
            // 淘客链接 增加或!替换tbk字段
            if ($type == 'tbk') {
                if (empty($value['ali_click'])) {
                    if ($value['source'] == 1) {
                        // 淘宝
                        $list[$key]['tbk'] = ' biz-itemid="' . $value['goods_id'] . '" isconvert=1 href="https://item.taobao.com/item.htm?id=' . $value['goods_id'] . '"';
                    } else if ($value['source'] == 2) {
                        // 天猫
                        $list[$key]['tbk'] = ' biz-itemid="' . $value['goods_id'] . '" isconvert=1 href="https://detail.tmall.com/item.htm?id=' . $value['goods_id'] . '"';
                    }
                } else {
                    // 淘客链接
                    $list[$key]['tbk'] = ' href="' . $value['ali_click'] . '"';
                }
            }
            // 图片优化显示
            if ($type == 'optimg' && isset($value[$cv['field']])) {
                // 后缀默认为关闭
                $cv['ext'] = isset($cv['ext']) ? $cv['ext'] : false;
                // 优化后的图片显示
                $list[$key][$cv['field']] = optimg($value[$cv['field']], $cv['width'], $cv['height'], $cv['ext']);
                // 保留默认图片
                // $list[$key]['_origin_'.$cv['field']] = optimg($value[$cv['field']], $cv['width'], $cv['height'], $cv['ext']);
            }
            // 粉丝福利购链接生成
            if ($type == 'uland') {
                if (!empty($cv['field'])) {
                    $list[$key]['uland_link'] = taobao_uland_link($value['goods_id'], $value['quan_link'], $cv['field']);
                } else {
                    $list[$key]['uland_link'] = taobao_uland_link($value['goods_id'], $value['quan_link']);
                }
            }

            // 判断是否上新
            if ($type == 'is_new') {
                if (!empty($value[$cv['field']])) {
                    $list[$key]['is_new'] = is_current_time($value[$cv['field']]);
                }
            }

            // 判断是否空值，是空值则给默认值
            if ($type == 'is_empty') {
                if (empty($value[$cv['field']])) {
                    $list[$key][$cv['field']] = $cv['default_value'];
                }
            }
        }

    }
    return $list;
}
/**
 * 转换 sql将带有预定义表前缀的sql,转换成真实的sql
 * @param  [string] $sql [带有预定义表前缀的sql]
 * @return [string]      [真实的sql]
 */
function c_sql($sql)
{
    return str_replace(DB_PREFIX_YD, DB_PREFIX, $sql);
}

function &ini_config($key)
{
    static $_ini_config = array();
    $file_path = ROOT . 'config/' . $key . '.ini.php';
    if (!isset($_arr_config[$key])) {

        if (file_exists($file_path)) {
            $_ini_config[$key] = include $file_path;
        } else {
            echo $file_path . ' not exist!';exit;
        }
    }
    return $_ini_config[$key];
}
function &inc_config($key)
{
    static $_inc_config = array();

    if (!isset($_arr_config[$key])) {
        $file_path = ROOT . 'config/' . $key . '.' . ENVIRONMENT . '.inc.php';
        if (!file_exists($file_path)) {

            $file_path = ROOT . 'config/' . $key . '.inc.php';
            if (!file_exists($file_path)) {
                exit($file_path . 'not exist!');
            }

        }
        $_inc_config[$key] = include $file_path;
    }
    return $_inc_config[$key];
}
//过滤禁用的关键字
function filter_key($str)
{
    if (empty($str)) {
        return '';
    }

    $keys_inc = &inc_config('filter_keys');
    $key_arr = explode('|', $keys_inc['keys']);
    foreach ($key_arr as $key => $value) {
        $str = str_replace($value, str_pad("", iconv_strlen($value), "*"), $str);
    }
    return $str;
}
//正则验证
function preg_key($str, $key)
{
    $CI = &get_instance();
    $rule = preg_rule($key);
    if (!$rule) {
        if (isset($CI->form_validation)) {
            $CI->form_validation->set_message('preg_key', '%s 正则不存在');
        }

        return false;
    }
    $result = preg_match($rule, $str);
    if ($result) {
        return true;
    } else {
        if (isset($CI->form_validation)) {
            $CI->form_validation->set_message('preg_key', '%s 格式错误');
        }

        return false;
    }
}
//正则
function preg_rule($key)
{
    $rule = &ini_config('preg_rule');
    if (isset($rule[$key])) {
        return $rule[$key];
    } else {
        return false;
    }

}
/**
 * 返回原始文件url 或html code
 * @param  varchar $file  文件地址
 * @param  varchar $media css media类型
 * @param  bool $rule 是否返回文件url 默认为false
 * @return varchar       [description]
 */
function static_original_url($file, $media = false, $rule = false)
{
    if (!$file) {
        return '';
    }

    $folder = STATIC_URL;
    $file_name = $file . '?v=' . STATIC_V;
    $html = '';
    if (strrpos($file, '.js')) {
        $html = '<script src="' . $folder . $file_name . '" type="text/javascript" charset="utf-8"></script>';
    } else if (strrpos($file, '.css')) {
        $media = in_array($media, array('screen', 'print')) ? $media : 'screen';
        $html = '<link rel="stylesheet" href="' . $folder . $file_name . '" type="text/css" media="' . $media . '" charset="utf-8">';
    }
    if ($rule) {
        return $folder . $file_name;
    } else {
        return $html . PHP_EOL;
    }

}
/**
 * 返回原始文件url 或html code
 * @param  varchar $file  文件地址
 * @param  varchar $media css media类型
 * @param  bool $rule 是否返回文件url 默认为false
 * @return varchar       [description]
 */
function admin_original_url($file, $media = false, $rule = false)
{
    if (!$file) {
        return '';
    }

    $folder = ADMIN_URL;
    $file_name = $file . '?v=' . STATIC_V;
    $html = '';
    if (strrpos($file, '.js')) {
        $html = '<script src="' . $folder . $file_name . '" type="text/javascript" charset="utf-8"></script>';
    } else if (strrpos($file, '.css')) {
        $media = in_array($media, array('screen', 'print')) ? $media : 'screen';
        $html = '<link rel="stylesheet" href="' . $folder . $file_name . '" type="text/css" media="' . $media . '" charset="utf-8">';
    }
    if ($rule) {
        return $folder . $file_name;
    } else {
        return $html . PHP_EOL;
    }

}
/**
 * 返回文件url 或 html code
 * @param  [type]  $url  [description]
 * @param  boolean $rule [description]
 * @return [type]        [description]
 */
function static_url($file, $media = false, $rule = false)
{
    if (!$file) {
        return '';
    }

    $folder = STATIC_URL;
    $file_name = $file . '?v=' . STATIC_V;
    $html = '';
    if (strrpos($file, '.js')) {
        $folder .= 'js/';
        $html = '<script src="' . $folder . $file_name . '" type="text/javascript" charset="utf-8"></script>';
    } else if (strrpos($file, '.css')) {
        $media = in_array($media, array('screen', 'print')) ? $media : 'screen';
        $folder .= 'css/';
        $html = '<link rel="stylesheet" href="' . $folder . $file_name . '" type="text/css" media="' . $media . '" charset="utf-8">';
    }
    if ($rule) {
        return $folder . $file_name;
    } else {
        return $html . PHP_EOL;
    }

}
/**
 * 返回文件url 或 html code
 * @param  [type]  $url  [description]
 * @param  boolean $rule [description]
 * @return [type]        [description]
 */
function static_plus_url($plus, $file, $media = false, $rule = false)
{
    $plugins_folder = "plugins/{$plus}/";
    if (!$file) {
        return '';
    }

    $folder = STATIC_URL . $plugins_folder;
    $file_name = $file . '?v=' . STATIC_V;
    $html = '';
    if (strrpos($file, '.js')) {
        $folder .= 'js/';
        $html = '<script src="' . $folder . $file_name . '" type="text/javascript" charset="utf-8"></script>';
    } else if (strrpos($file, '.css')) {
        $media = in_array($media, array('screen', 'print')) ? $media : 'screen';
        $folder .= 'css/';
        $html = '<link rel="stylesheet" href="' . $folder . $file_name . '" type="text/css" media="' . $media . '" charset="utf-8">';
    }
    if ($rule) {
        return $folder . $file_name;
    } else {
        return $html . PHP_EOL;
    }

}
/**
 * 返回文件url 或 html code
 * @param  [type]  $url  [description]
 * @param  boolean $rule [description]
 * @return [type]        [description]
 */
function static_site_url($site, $file, $media = false, $rule = false)
{

    if (!$file) {
        return '';
    }

    $folder = STATIC_URL . $site . '/';
    $file_name = $file . '?v=' . STATIC_V;
    $html = '';
    if (strrpos($file, '.js')) {
        $folder .= 'js/';
        $html = '<script src="' . $folder . $file_name . '" type="text/javascript" charset="utf-8"></script>';
    } else if (strrpos($file, '.css')) {
        $media = in_array($media, array('screen', 'print')) ? $media : 'screen';
        $folder .= 'css/';
        $html = '<link rel="stylesheet" href="' . $folder . $file_name . '" type="text/css" media="' . $media . '" charset="utf-8">';
    }
    if ($rule) {
        return $folder . $file_name;
    } else {
        return $html . PHP_EOL;
    }

}
//include_once view
function include_views($file_name, $dir = 'inc')
{
    if ($dir) {
        include_once APPPATH . 'views/' . $dir . '/' . $file_name;
    } else {
        include_once APPPATH . 'views/' . $file_name;
    }
}

/**
 * 保持图片的尺寸大小不变
 * 压缩文件大小，并保持极高的像素
 * $src_img 原始图片路径
 * $dst_img 目标图片路径
 * $new_width 新的宽度
 * $new_height新的高度
 * $quality   图片质量
 * $thumb=array('width', 缩略图宽度
 *      'height',缩略图高度
 *      'dst_img' 缩略图目标路径
 * )
 */
function compress_image($src_img, $dst_img, $new_width = 0, $new_height = 0, $quality = 100, $thumb = array())
{
    $img_data = getimagesize($src_img);
    if (!$img_data) {
        return false;
    }

    $width = $img_data[0];
    $height = $img_data[1];
    $type = $img_data[2];
    $new_width = !empty($new_width) ? $new_width : $width;
    $new_height = !empty($new_height) ? $new_height : $height;

    switch ($type) {
        case 1:
            header('Content-Type:image/gif');
            $image_wp = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromgif($src_img);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagejpeg($image_wp, $dst_img, $quality);
            if (!empty($thumb) && is_array($thumb)) {
                $image_wp_thumb = imagecreatetruecolor($thumb['width'], $thumb['height']);
                imagecopyresampled($image_wp_thumb, $image, 0, 0, 0, 0, $thumb['width'], $thumb['height'], $width, $height);
                imagejpeg($image_wp_thumb, $thumb['dst_img'], $quality);
                imagedestroy($image_wp_thumb);
            }
            imagedestroy($image_wp);
            break;
        case 2:
            header('Content-Type:image/jpeg');
            $image_wp = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromjpeg($src_img);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagejpeg($image_wp, $dst_img, $quality);
            if (!empty($thumb) && is_array($thumb)) {
                $image_wp_thumb = imagecreatetruecolor($thumb['width'], $thumb['height']);
                imagecopyresampled($image_wp_thumb, $image, 0, 0, 0, 0, $thumb['width'], $thumb['height'], $width, $height);
                imagejpeg($image_wp_thumb, $thumb['dst_img'], $quality);
                imagedestroy($image_wp_thumb);
            }
            imagedestroy($image_wp);
            break;
        case 3:
            header('Content-Type:image/png');
            $image_wp = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefrompng($src_img);
            //png 图片背景透明处理
            $alpha = imagecolorallocatealpha($image_wp, 0, 0, 0, 127);
            imagefill($image_wp, 0, 0, $alpha);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagesavealpha($image_wp, true);
            $png_quality = (int) (ceil((100 - $quality) / 10));

            imagepng($image_wp, $dst_img, $png_quality);
            if (!empty($thumb) && is_array($thumb)) {
                $image_wp_thumb = imagecreatetruecolor($thumb['width'], $thumb['height']);
                //png 图片背景透明处理
                $alpha = imagecolorallocatealpha($image_wp_thumb, 0, 0, 0, 127);
                imagefill($image_wp_thumb, 0, 0, $alpha);
                imagecopyresampled($image_wp_thumb, $image, 0, 0, 0, 0, $thumb['width'], $thumb['height'], $width, $height);
                imagesavealpha($image_wp_thumb, true);

                imagepng($image_wp_thumb, $thumb['dst_img'], $png_quality);
                imagedestroy($image_wp_thumb);
            }
            imagedestroy($image_wp);
            break;
    }

    return true;
}
//md5签名
function md5_sign($data)
{
    ksort($data);
    $par_str = '';
    foreach ($data as $k => $v) {
        $par_str .= $v;
    }
    return md5($par_str . SECRET_KEY);
}
// 获取毫秒时间戳
function getMillisecond()
{
    list($t1, $t2) = explode(' ', microtime());
    return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}

// 计算两个时间戳相差几天
function count_between_days($a, $b)
{
    $a_dt = getdate($a);
    $b_dt = getdate($b);
    $a_new = mktime(12, 0, 0, $a_dt['mon'], $a_dt['mday'], $a_dt['year']);
    $b_new = mktime(12, 0, 0, $b_dt['mon'], $b_dt['mday'], $b_dt['year']);
    return round(abs($a_new - $b_new) / 86400);
}
//判断是否处在当前时间段
if (!function_exists('is_current_time')) {
    function is_current_time($time)
    {
        if (empty($time) || !is_numeric($time)) {
            return false;
        }

        $c_time = strtotime(date('Ymd'));
        if ($time >= $c_time) {
            return true;
        }

        return false;
    }
}

/**
 * 优化淘宝cdn图片大小
 * @param  string  $picurl [description]
 * @param  integer $width  [description]
 * @param  integer $height [description]
 * @return [type]          [description]
 */
function optimg($picurl = '', $width = 260, $height = 260, $showext = false)
{
    // 过滤webp
    $picurl = str_replace('.webp', '', $picurl);

    // 上传路径图片直接返回
    if (strpos($picurl, UPLOAD_URL) !== false) {
        return $picurl;
    }

    // 兼容本地上传图片
    if (strpos($picurl, 'http://') === false && strpos($picurl, 'https://') === false) {
        return UPLOAD_URL . $picurl;
    }

    // 过滤alicdn.com图片
    //    if (strpos($picurl, '.alicdn.com') !== false) {
    //        return $picurl;
    //    }
    // if(strpos($picurl, '.tbcdn.cn') !== false || strpos($picurl, '.taobaocdn.com') !== false) {
    //     return $picurl;
    // }

    $pattern = '/^(.*)alicdn.com(.*).(gif|jpg|jpeg|png)_(.*)$/i';
    if (preg_match($pattern, $picurl)) {
        return $picurl;
    } else {
        $ext = pathinfo($picurl, PATHINFO_EXTENSION);
        if (in_array($ext, ['gif', 'jpg', 'jpeg', 'png'])) {
            if ($showext) {
                return $picurl . '_' . $width . 'x' . $height . '.jpg';
            } else {
                return $picurl . '_' . $width . 'x' . $height;
            }
        } else {
            return $picurl;
        }
    }
}
/**↓↓↓↓↓↓↓ host start ↓↓↓↓↓↓**/
/**
 * 自动路由到响应机器上
 */
function get_db_connect($useDefault = false)
{

    //从域名中获取路由
    $host = get_host();
    //如果没有,则初始化个数据库连接
    $ci = &get_instance();
    if ($host['c_name'] != 'default_host' && $useDefault) {
        if (!isset($ci->db_default)) {
            $hosts = &inc_config('hosts');
            $default_host = $hosts['default'];
            $db_default_config = get_db_config($default_host);
            $ci->db_default = $ci->load->database($db_default_config);
            //把数据库路由放到周期变量中
            // set_to_vars('db_default', $ci->db_default);
        }
        return $ci->db_default;
    }
    if (!isset($ci->db_host)) {
        $db_host_config = get_db_config($host);
        $ci->db_host = $ci->load->database($db_host_config, true);
        // set_to_vars('db_host', $ci->db_host);
    }
    return $ci->db_host;

    //写入连接索引
    // $host_list = get_from_vars('db_connect_list');
    // if (empty($host_list)) {
    //     $host_list = array();
    // }
    // $host_list[$host] = $host;
    // set_to_vars('db_connect_list', $host_list);
}
/**
 * 得到主机的数据库配置
 * @param  [type] $host [description]
 * @return [type]       [description]
 */
function get_db_config($host)
{
    $db_info = $host['db_info'];
    $config['hostname'] = $db_info['hostname'];
    $config['username'] = $db_info['username'];
    $config['password'] = $db_info['password'];
    $config['database'] = $db_info['database'];
    $config['dbdriver'] = DB_DRIVER;
    $config['dbprefix'] = DB_PREFIX;
    $config['pconnect'] = false;
    $config['db_debug'] = (ENVIRONMENT !== 'production');
    $config['cache_on'] = false;
    $config['cachedir'] = '';
    $config['char_set'] = 'utf8';
    $config['dbcollat'] = 'utf8_general_ci';
    return $config;
}
/**
 * 得到主机
 * @return [type] [description]
 */
function get_host()
{
    if (!empty(get_from_vars('host'))) {
        return get_from_vars('host');
    }

    $host_name = $_SERVER['SERVER_NAME'];
    if (empty($host_name)) {
        exit('you must use set_to_vars("db_host","xxx") first!');
    }
    $hosts = &inc_config('hosts');

    if (isset($hosts[$host_name])) {
        $host = $hosts[$host_name];
    } else {
        $host = $hosts['default'];
    }

    set_to_vars('host', $host);
    return get_from_vars('host');
}
/**
 * 得到主机的数据库配置
 * @param  [type] $host [description]
 * @return [type]       [description]
 */
function get_shard_config($host)
{
    $config['hostname'] = $host['hostname'];
    $config['username'] = $host['username'];
    $config['password'] = $host['password'];
    $config['database'] = $host['database'];
    $config['dbdriver'] = DB_DRIVER;
    $config['dbprefix'] = $host['dbprefix'] ?? '';
    $config['pconnect'] = false;
    $config['db_debug'] = (ENVIRONMENT !== 'production');
    $config['cache_on'] = false;
    $config['cachedir'] = '';
    $config['char_set'] = 'utf8';
    $config['dbcollat'] = 'utf8_general_ci';
    return $config;
}
/**
 * 获取var中的数据
 * @param type $key
 * @return string
 */
function get_from_vars($key)
{
    $CI = &get_instance();
    if (isset($CI->vars) && is_array($CI->vars)) {
        if (isset($CI->vars) && isset($CI->vars[$key])) {
            return $CI->vars[$key];
        }

    }
    return null;
}

/**
 * 删除var中的数据
 * @param type $key
 * @return string
 */
function del_from_vars($key = 'db')
{
    $CI = &get_instance();
    if (isset($CI->vars) && is_array($CI->vars)) {
        if (isset($CI->vars[$key])) {
            unset($CI->vars[$key]);
        }

    }
}

/**
 * 设置var中的数据
 * @param type $key
 * @param type $var_str
 */
function set_to_vars($key, $var_str = '')
{
    $CI = &get_instance();
    if (isset($CI->vars)) {
        if (is_array($CI->vars)) {
            $CI->vars[$key] = $var_str;
        } else {
            exit('error:vars is not array');
        }

    } else {
        $CI->vars = [];
        $CI->vars[$key] = $var_str;
    }
}
/**↑↑↑↑↑↑↑ host end ↑↑↑↑↑↑**/

/**
 * 订单支付过期时间
 * @param  [type] $timestamp [description]
 * @return [type]            [description]
 */
function order_pay_expire($timestamp = 0, $minus = false)
{
    $timestamp = $timestamp ? $timestamp : time();
    return $minus ? $timestamp - 15 * 60 : $timestamp + 15 * 60;
}

/**
 * 订单售后申请过期时间
 * @param  [type] $timestamp [description]
 * @return [type]            [description]
 */
function order_refund_expire($timestamp = 0, $minus = false)
{
    $timestamp = $timestamp ? $timestamp : time();
    return $minus ? $timestamp - 24 * 60 * 60 : $timestamp + 24 * 60 * 60;
}

if (!function_exists('format_rate')) {
    /**
     * 格式化出发数据
     * @param $number
     * @param int $divide_number
     */
    function format_rate($number, $divide_number = 0, $format = 2)
    {
        if (!is_numeric($number) || !is_numeric($divide_number) || $divide_number == 0) {
            $rate = 0;
        } else {
            $rate = $number / $divide_number;
        }

        $rate = $rate * 100;
        return number_format($rate, $format);
    }
}

if (!function_exists('filter_bom')) {
    /**
     * 过滤包含BOM头的字符串
     * @param string $str
     */
    function filter_bom($str)
    {
        if (ord($str[0]) == 239 && ord($str[1]) == 187 && ord($str[2]) == 191) {
            $str = substr($str, 3);
        }

        return $str;
    }
}
/**
 * 获取当前系统aid
 */
if(!function_exists('get_aid'))
{
    function get_aid()
    {
        $aid = null;
        $ci = &get_instance();
        isset($ci->aid) && $aid = $ci->aid;
        $aid ?? isset($ci->s_user->aid) && $aid = $ci->s_user->aid;
        if($aid !== null)
            return $aid;
        else
            throw new Exception(" no aid ");
    }
}
/**
 * 获取scrm版本号分隔id
 * @return [type] [description]
 */
if(!function_exists('get_scrm_spe_aid'))
{
    
    function get_scrm_spe_aid()
    {
        if(in_array(ENVIRONMENT, ['production']))
            return 2853;
        else
            return 1227;
    }
}
/**
 * 是否使用是新的scrm
 */
if(!function_exists('is_new_scrm'))
{

    function is_new_scrm($aid=null)
    {
        //已全部升级为新版本，无需再判断,请去除
        return true;
    }
}
/**
 * 根据aid值获取scrm的配置
 */
if(!function_exists('get_scrm_config'))
{
    function get_scrm_config()
    {
        if(is_new_scrm())
            return inc_config('scrm_new');
        else
            return inc_config('scrm');
    }
}
if(!function_exists('get_secret_cookie'))
{
    //获取加密cookie
    function get_secret_cookie($name,$signKey='')
    {
        $item = get_cookie($name);
        if(!$item)
            return null;

        $item = @unserialize($item);
        if(!$item)
            return null;

        if(isset($item['value']) && isset($item['time']) && isset($item['sign']))
        {
            $sign = md5($item['value'].$item['time'].$signKey.SECRET_COOKIE_KEY);
            if($sign == $item['sign'])
                return $item['value'];
            else
                return null;

        }
        else
            return null;
    }
}
if(!function_exists('set_secret_cookie'))
{
    //设置加密cookie 
    function set_secret_cookie($name,$value,$expireTime=3600,$signKey='')
    {
        $item['time'] = time();
        $item['value'] = $value;
        $item['sign'] = md5($item['value'].$item['time'].$signKey.SECRET_COOKIE_KEY);
        set_cookie($name,serialize($item),$expireTime);
    }
}