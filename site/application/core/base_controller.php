<?php
/**
 * @Author: binghe
 * @Date:   2017-08-04 14:15:54
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-30 15:47:58
 */
/**
 * 控制器基类
 */
class base_controller extends CI_Controller
{
    // public $check = null; //检测
    public $url_class = '';
    public $url_method = '';
    public $url_directory = '';
    public $json_do = null;
    public $check = null;
    //登录用户
    public $s_user=null;

    public function __construct()
    {
        parent::__construct();
        //获取路由中的class 和 method
        if ($this->url_class === '') {
            $this->url_class = strtolower($this->router->class);
        }
        if ($this->url_method === '') {
            $this->url_method = strtolower($this->router->method);
        }
        if ($this->url_directory === '') {
            $this->url_directory = strtolower($this->router->directory);
        }
        $this->check = new ci_check();
        $this->json_do = new json_do();


        //自动登录
        $this->_auto_login();
       
    }
    //自动登录
    private function _auto_login()
    {
        $c_user = get_cookie('saas_user');
        //不存在返回
        if(!$c_user)
            return;
        //解密
        $c_user = $this->encryption->decrypt($c_user);
        if(!$c_user)
            return;
        $s_user = @unserialize($c_user);
        if(is_subclass_of($s_user,'s_base_user_do'))
        {
            $this->s_user=$s_user;
        }
    }
    /**
     * 得到表单数据
     * @param  array $rule 规则
     * @return array       表单值
     */
    public function form_data($rule)
    {
        $data = array();
        foreach ($rule as $r) {
            $xss_clean = true;
            if (isset($r['xss_clean']) && $r['xss_clean'] === false) {
                $xss_clean = false;
            }

            $data[$r['field']] = $this->input->post($r['field'], $xss_clean);
        }
        return $data;
    }

    /**
     * 自动加载视图
     * @param $method
     * @param array $params
     * @return mixed
     */
    public function _remap($method, $params = array())
    {
        if (method_exists($this, $method)) {

            return call_user_func_array(array($this, $method), $params);
        }

        //对目录文件的处理
        $dir = $this->url_directory . strtolower($this->url_class);
        $file_name = strtolower($this->url_method);

        $file_path = strtolower(APPPATH . 'views/' . $this->url_directory . $this->url_class . '/' . $this->url_method . EXT);
        $data_method = $file_name . '_data';

        if (file_exists($file_path)) {

            $v_data = array();
            $post = $this->input->post();
            $get = $this->input->get();

            if (method_exists($this, $data_method)) {
                $v_data = $this->$data_method(array_merge($post, $get));
            }

            $this->load->view($dir . '/' . $file_name, $v_data);
        } else {
            show_404();
        }

    }
    //实现错误方法
    public function onException($e)
    {
        log_message('error',"service error: code-{$e->getCode()},msg-{$e->getMessage()}");
        $this->_error(500,$e->getMessage());
    }
    protected function _error($errCode,$errMsg)
    {
        if(is_ajax())
        {
            $this->json_do->set_error('004',$errMsg);
        }
        else
            show_error($errMsg);
    }
}
