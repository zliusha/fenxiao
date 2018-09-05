<?php
/**
 * 前后端分离发布器
 */
class dist_controller extends CI_Controller
{
    public $url_class = '';
    public $url_method = '';

    public function __construct()
    {
        parent::__construct();

        if (empty($this->url_class)) {
            $this->url_class = strtolower($this->router->class);
        }

        if (empty($this->url_method)) {
            $this->url_method = strtolower($this->router->method);
        }
    }

    // 发布输出html
    public function dist($filepath = '')
    {
        if (!$filepath) {
            return;
        }
        $filepath = APPPATH . 'dist' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filepath);
        if (file_exists($filepath)) {
            ob_start();
            require_once $filepath;
            $content = ob_get_contents();
            ob_end_clean();
            echo $content;
        } else {
            exit('文件不存在');
        }
    }
}
