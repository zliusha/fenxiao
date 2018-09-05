<?php

/**
 * @Author: binghe
 * @Date:   2018-08-21 09:42:47
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-21 10:47:25
 */
/**
 * 公众号校验
 */

use Service\DbFrame\DataBase\WmMainDbModels\XcxAppDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGzhConfigDao;
class Mp extends CI_controller
{
    /**
     * 公众号文件校验
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function index($key)
    {
        $wmGzhConfigDao = WmGzhConfigDao::i();
        $m_wm_gzh_config = $wmGzhConfigDao->getOne(['verify_file_name' => $key . '.txt']);
        if ($m_wm_gzh_config) {
            if (file_exists(UPLOAD_PATH . $m_wm_gzh_config->verify_file_path)) {
                $string = read_file(UPLOAD_PATH . $m_wm_gzh_config->verify_file_path);
                echo $string;
                exit;
            }
        }

        echo 'error';
    }

    /**
     * 小程序普通链接二维码地址 文件校验
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function meal($key)
    {
        $xcxAppDao = XcxAppDao::i();
        $m_xcx_app = $xcxAppDao->getOne(['verify_file_name' => $key . '.txt']);
        if ($m_xcx_app) {
            if (file_exists(UPLOAD_PATH . $m_xcx_app->verify_file_path)) {
                $string = read_file(UPLOAD_PATH . $m_xcx_app->verify_file_path);
                echo $string;
                exit;
            }
        }

        echo 'error';
    }
}