<?php
/**
 * @Author: binghe
 * @Date:   2017-08-04 14:15:54
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 16:41:47
 */
use Service\Cache\WmGzhConfigCache;
use Service\Enum\SaasEnum;

/**
 * 控制器基类
 */
class base_controller extends CI_Controller
{
    const SAAS_ID = SaasEnum::YD;
    public $url_class = '';
    public $url_method = '';
    public $url_directory = '';
    public $json_do = null;
    public $check = null;
    //登录用户
    public $s_user = null;
    //当前所选的门店,0代表综合门店
    public $currentShopId=null;
    //是否总部
    public $is_zongbu = 0;
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
        // 无需登录的控制器
        $no_auth = ['passport', 'passport_api', 'sub', 'sub_api', 'mobile_api', 'common_file', 'test', 'test_func', 'alipay'];

        //cookie自动登录
        $this->_auto_login();
        //验证登录
        if (!$this->s_user) {
            if (!in_array($this->url_class, $no_auth)) {
                if (is_ajax()) {
                    $this->json_do->set_error('004', '用户未登录或已超时');
                } else {
                    redirect(SITE_URL . 'passport/login');
                }
            }
        }
    }
    // 自动登录
    private function _auto_login()
    {
        $sassUser = get_cookie('saas_user');
        //不存在返回
        if (!$sassUser) {
            return;
        }

        //解密
        $sassUser = $this->encryption->decrypt($sassUser);
        if (!$sassUser) {
            delete_cookie('saas_user');//不是正确的cookie删除
            return;
        }
        //赋值,自动加载上一次门店
        $s_user = @unserialize($sassUser);
        if (get_class($s_user) == 's_saas_user_do') {
            $this->s_user = $s_user;
            $item = $this->url_class.'/'.$this->url_method;
            $noAuth = ['shop/toggle','saas/login'];
            if (!in_array($item, $noAuth)) {
                $this->_load_shops();
                if($this->currentShopId === null)
                {
                    if (is_ajax()) {
                        $this->json_do->set_error('004', '请选择门店');
                    } else {
                        redirect(SAAS_URL);
                    }
                }
            }
            
        }
    }
    //自动加载店铺
    private function _load_shops()
    {
        $currentShopId = get_secret_cookie('c_wm_shop_id',$this->s_user->id);
        if($currentShopId===null )
            return;
        if(!is_numeric($currentShopId))
        { 
            delete_cookie('c_wm_shop_id');
            return;
        }
        $currentShopId = (int)$currentShopId;
        //子账号不能有总部管理权限
        if(!$this->s_user->is_admin && $currentShopId==0 )
        {
            delete_cookie('c_wm_shop_id');
            return;
        }
        //这里理应验证门店
        $this->currentShopId = $currentShopId;
        if($this->currentShopId == 0)
            $this->is_zongbu = 1;
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
    /**
     * 错误，自动识别ajax
     * @param  [type] $code [description]
     * @param  [type] $msg  [description]
     * @return [type]       [description]
     */
    public function _error($code,$msg)
    {
        if (is_ajax()) {
            $this->json_do->set_error($code, $msg);
        } else {
            $data['code']=$code;
            $data['msg']=$msg;
            $this->load->view('main/permission',$data);
            $this->output->_display();
            exit;
        }
    }
    //实现错误方法
    public function onException($e)
    {
        log_message('error',"service error: code-{$e->getCode()},msg-{$e->getMessage()}");
        $this->_error(500,$e->getMessage());
    }
}
