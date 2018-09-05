<?php
/**
 * @Author: binghe
 * @Date:   2016-06-14 15:24:44
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-11-01 19:57:13
 */
class ci_uploads
{
    public $ini_config;
    public function __construct()
    {
        $this->ini_config = &ini_config('upload');
    }
    public function __get($name)
    {
        $ci = &get_instance();
        return $ci->$name;
    }
    /*
    文件上传设置
     */
    public function do_upload($conf = array())
    {
        $_conf = array(
            'type' => 'img', //类型
            'field' => 'userfile', //上传name
            'folder' => '', //上传地址
            'time_folder' => true, //是否开始时间目录
            'filter_size' => true, //是否智能控制图片大小，只有type='img'时起效
            'up_config' => array(),
        );
        //递归合并
        $_conf = array_merge($_conf, $conf);
        //文件目录
        $folder = strtolower(trim($_conf['folder']));
        if ($_conf['time_folder']) {
            $folder .= ('/' . date('Ymd') . '/');
        }
        $upload_path = UPLOAD_PATH . $folder;
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        //图片config
        $up_config = array(
            'upload_path' => $upload_path,
            'encrypt_name' => true, //是否重命名文件
            'allowed_types' => $this->ini_config['img_types'], //默认图片类型
            'max_size' => $this->ini_config['max_size'],
        );
        //控制上传的类型
        if ($_conf['type'] == 'img' && $_conf['filter_size']) {
            //控制图片的大小
        }
        switch ($_conf['type']) {
            case 'img':
                break;
        }
        $up_config = array_merge($up_config, $_conf['up_config']);
        $this->load->library('upload', $up_config);
        if ($this->upload->do_upload($_conf['field'])) {
            $data = $this->data();

            //返回data
            $vdata['new_filepath'] = $folder . $data['file_name'];
            $vdata['old_filename'] = $data['orig_name'];
            $vdata['new_filename'] = $data['file_name'];

            $vdata['return_data'] = $data;
            return $vdata;
        } else {
            return false;
        }
    }
    public function display_errors()
    {
        return $this->upload->display_errors();
    }
    public function data()
    {
        return $this->upload->data();
    }

}
