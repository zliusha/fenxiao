<?php
include_once(LIBS_PATH.'helpers/common_helper.php');
include_once(LIBS_PATH.'helpers/funs_helper.php');
date_default_timezone_set('Asia/Shanghai');
/*
返回站点url
 */
function c_site_url($re_url)
{
    return ADMIN_URL.$re_url;
}

/**
 * 获取一段时间内有几个月份
 * @param $time  开始时间 较小的 时间戳
 * @param $e_time结束时间 较大的 时间戳
 */
function get_count_month($time, $e_time){

    $date_time = date('Ym', $time).'01';
    $i = 1;
    while(true){
        if(strtotime($date_time.' +'.$i.' month')  > $e_time) break;
        $i++;
    }
    return $i;
}

/**
 * 遍历获取目录下的指定类型的文件
 * @param $path
 * @param array $files
 * @return array
 */
function getfiles($path, $allowFiles, &$files = array())
{
    if (!is_dir($path)) return null;
    if(substr($path, strlen($path) - 1) != '/') $path .= '/';
    $handle = opendir($path);
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            $path2 = $path . $file;
            if (is_dir($path2)) {
                getfiles($path2, $allowFiles, $files);
            } else {
                if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
                    $files[] = array(
                        'url'=> substr($path2, strlen(UPLOAD_PATH)),
                        'mtime'=> filemtime($path2)
                    );
                }
            }
        }
    }
    return $files;
}
