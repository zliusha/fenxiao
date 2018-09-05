<?php
include_once(LIBS_PATH.'helpers/common_helper.php');
include_once(LIBS_PATH.'helpers/funs_helper.php');
date_default_timezone_set('Asia/Shanghai');
/**
 * 静态资源路径
 * @param $img_path
 * @param string $folder
 * @return string
 */
function static_img_url($img_path, $folder='djadmin/'){
    return STATIC_URL.$folder.$img_path;
}

/**
 * 当前网站 url
 * @param $re_url
 * @return string
 */
function c_site_url($re_url='')
{
    return DJADMIN_URL.$re_url;
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

/**
 * 橱窗装修转链
 *
 */
if(!function_exists('do_link')){
    /**
     * @param int $item_type 0商品，1商品分类，2自定义链接，3自定义页面
     * @param string $ext 当item_type值0:dg_goods_id 值1:category_id 值2:link 值3:custom_page_id
     * @param int $cid
     * @return string
     */
    function do_link($item_type=0, $ext='', $cid=0)
    {
        $link = '';
        switch($item_type)
        {
            case 0://商品链接
                $link = MOBILE_URL.'shopwindow/info/'.$ext.'?cid='.$cid;
                break;
            case 1://分类链接
                $link = MOBILE_URL.'shopwindow/cate/'.$ext.'?cid='.$cid;
                break;
            case 2://自定义链接
                $link = $ext;
                break;
            case 3://自定义页面
                $link = MOBILE_URL.'shopwindow/cc_page/'.$ext.'?cid='.$cid;
                break;
            default:
                break;
        }
        return $link;
    }
}