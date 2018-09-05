<?php
include_once LIBS_PATH . 'helpers/common_helper.php';
include_once LIBS_PATH . 'helpers/funs_helper.php';
date_default_timezone_set('Asia/Shanghai');
/**
 * 静态资源路径
 * @param $img_path
 * @param string $folder
 * @return string
 */
function static_img_url($img_path, $folder='vshop/'){
  return STATIC_URL.$folder.$img_path;
}

/**
 * 当前网站 url
 * @param $re_url
 * @return string
 */
function c_site_url($re_url='')
{
  return SITE_URL.$re_url;
}

/**
 *  加载 配置文件
 * @param $file_name
 * @param string $dir
 */
function c_include_views($file_name, $arr = array(), $dir='inc')
{
  if(!empty($arr)){
    foreach($arr as $key => $val){
      $$key = $val;
    }
  }
  if($dir)
    include(APPPATH.'views/'.$dir.'/'.$file_name);
  else
    include(APPPATH.'views/'.$file_name);
}

/**
 * liusha
 * 获取前面的url
 */
function get_refer_url(){
  return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
}


/**
 * liusha
 *获取当前url
 */
function get_url(){
  return 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}
