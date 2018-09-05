<?php
/**
 * @Author: binghe
 * @Date:   2016-07-20 11:39:22
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-01-22 15:19:39
 */
/**
 * json输出格式
 */
class json_do
{
    public $success = false;
    public $code = '000';
    public $data;
    public $msg = '';

    private $code_arr;
    private $CI;
    private $msg_pre = '';

    public function __construct($pre = '')
    {
        $this->CI = &get_instance();
        if (!empty($pre)) {
            $this->msg_pre = $pre;
        }

    }
    //设置错误消息
    public function set_error($code, $msg = '', $out_put = true)
    {
        $this->code = $code;
        if (!empty($msg)) {
            $this->msg = $msg;
        }

        if ($out_put) {
            $this->out_put();
        }
    }
    public function set_error_pre($code, $msg_pre, $out_put = true)
    {
        $this->code = $code;
        if (!empty($msg_pre)) {
            $this->msg_pre = $msg_pre;
        }

        if ($out_put) {
            $this->out_put();
        }

    }
    /**
     * [设置输出消息]
     * @param [string] $msg 输出消息
     */
    public function set_msg($msg)
    {
        $this->msg = $msg;
    }
    public function set_data($data)
    {
        $this->data = $data;
    }
    public function __get($property_name)
    {
        return isset($this->$property_name) ? $this->property_name : null;
    }
    public function __set($property_name, $value)
    {
        if (isset($this->property_name)) {
            $this->property_name = $value;
        }
    }
    //输出json
    public function out_put($exit = true)
    {
//        $csrf_protection = $this->CI->config->item('csrf_protection');
//        //post提交且开启了csrf保护，重新生成cookie
//        if (is_post() && $csrf_protection) {
//            $c_name = $this->CI->config->item('csrf_cookie_name');
//            $c_value = $this->CI->security->get_csrf_hash();
//            $c_expire = $this->CI->config->item('csrf_expire');
//            set_cookie($c_name, $c_value, $c_expire);
//        }

        //自动引用错误消息
        if ($this->code && empty($this->msg)) {
            if (empty($this->code_arr)) {
                $this->code_arr = include APPPATH . 'config/code_error.php';
            }
            $this->msg = $this->msg_pre . @$this->code_arr[$this->code];
        }
        if ($this->code == '000') {
            $this->success = true;
        } else {
            $this->success = false;
        }
        $this->CI->output->set_content_type('application/json')->set_output(json_encode($this));
        if ($exit) {
            $this->CI->output->_display();
            exit;
        }
    }
}
