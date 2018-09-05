<?php
/**
 * @Author: binghe
 * @Date:   2016-07-08 14:43:22
 * @Last Modified by:   binghe
 * @Last Modified time: 2016-08-03 13:19:06
 */
/**
 * 基
 */
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainSysLogDao;

class base_controller extends CI_Controller
{
    public $check = null; //检测
    public $json_do = null;
    public $url_class = "";
    public $url_method = "";
    public $sys_log; //日志文件
    public $user_ini;
    public function __construct()
    {
        parent::__construct();
        //获取路由中的class 和 method
        if ($this->url_class === "") {
            $this->url_class = strtolower($this->router->class);
        }

        if ($this->url_method === "") {
            $this->url_method = strtolower($this->router->method);
        }


        $this->check = new ci_check();
        $this->json_do = new json_do();
        //加载日志文件
        $this->sys_log = ManageMainSysLogDao::i();
        $this->user_ini = &ini_config('user');
    }
    //自动加载视图
    public function _remap($method, $params = array())
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $params);
        }
        //对目录文件的处理
        $_index = strpos($this->url_method, '-');
        if ($_index) {
            $dir = "";
            $file_name = $this->url_method;

            $dir = substr($file_name, 0, $_index) . '/';
            $file_name = substr($file_name, $_index + 1);

            $file_path = strtolower(APPPATH . 'views/' . $dir . $file_name . EXT);
            if (file_exists($file_path)) {
                $this->load->view($dir . $file_name);
            } else {
                show_404();
            }

        } else {
            show_404();
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
            if(isset($r['xss_clean']) && $r['xss_clean']===false)
                $xss_clean = false;
            $data[$r['field']] = $this->input->post($r['field'], $xss_clean);
        }
        return $data;
    }
    /**
     * 设置memcached
     * @param  string $k 键
     * @param  mixed $v 值
     * @param  int $expires_in
     */
    public function memcached_set($k, $v, $expires_in = false)
    {
        if (!isset($this->cache)) {
            $this->load->driver('cache');
        }

        if ($this->cache->memcached->is_supported()) {
            $this->cache->memcached->save($k, $v, $expires_in);
        } else {
            throw new Exception('memcached is not supported!');
        }

    }
    /**
     * [slog 记录系统日志]
     * @param  varchar $log_description 描述
     * @param  $type            类型
     */
    protected function slog($log_description, $log_ref_id = null, $log_user = null, $type = null)
    {
        //未设置$type智能获取
        if ($type === null) {
            if (strpos($this->url_class, 'sys_') === 0) {
                $type = 0;
            } else {
                $type = 1;
            }
        }

        //自动获取已登录用户信息
        if ($log_user === null && isset($this->s_user)) {
            $log_user = $this->s_user->username;
        }

        $v_data['type'] = $type;
        $v_data['log_user'] = $log_user;
        $v_data['log_ip'] = $this->input->ip_address();
        $v_data['time'] = time();
        $v_data['log_ref_id'] = $log_ref_id;
        $v_data['log_description'] = $log_description;
        $this->sys_log->create($v_data);
    }

}
