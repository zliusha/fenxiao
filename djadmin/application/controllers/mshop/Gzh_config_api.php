<?php
/**
 * @Author: binghe
 * @Date:   2017-10-31 16:02:56
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 13:26:08
 */
use Service\Cache\WmGzhConfigCache;
/**
 * 公众号
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmGzhConfigDao;
class Gzh_config_api extends wm_service_controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->is_zongbu) {
            $this->json_do->set_error('004', '没有访问权限');
        }

    }
    /**
     * 开启开发者登录
     * @return [type] [description]
     */
    public function login_edit()
    {

        $rules = [
            ['field' => 'login_type', 'label' => '登录方式', 'rules' => 'trim|required|in_list[0,1]'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        if($fdata['login_type'] == '0')
        {
             $rules1 = [
                ['field' => 'app_id', 'label' => '公众号id', 'rules' => 'trim|required'],
            ['field' => 'app_secret', 'label' => '公众号密钥', 'rules' => 'trim|required'],
            ['field' => 'verify_file_name', 'label' => '公证文件原名称', 'rules' => 'trim|required'],
            ['field' => 'verify_file_path', 'label' => '验证文件路径', 'rules' => 'trim|required'],
            ];
            $this->check->check_ajax_form($rules1);
            $fdata1 = $this->form_data($rules1);
            $fdata = array_merge($fdata,$fdata1);
        }
       

        $this->_edit($fdata);
    }
    /**
     * 开启第三方登录
     * @return [type] [description]
     */
    public function login_swith()
    {
        $rules = [
            ['field' => 'login_type', 'label' => '登录方式', 'rules' => 'trim|required|in_list[0,1]']
            
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $this->_edit($fdata);
    }
    /**
     * 支付配置
     * @return [type] [description]
     */
    public function pay_edit()
    {
        $rules = [
            ['field' => 'mch_id', 'label' => '商户号', 'rules' => 'trim|required'],
            ['field' => 'key', 'label' => '商户支付密钥', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $this->_edit($fdata);
    }
    /**
     * 退款配置
     * @return [type] [description]
     */
    public function return_edit()
    {
        $rules = [
            ['field' => 'apiclient_cert_path', 'label' => 'apiclient_cert_path证书', 'rules' => 'trim|required'],
            ['field' => 'apiclient_key_path', 'label' => 'apiclient_cert证书', 'rules' => 'trim|required'],
            // ['field' => 'rootca_path', 'label' => 'rootca证书', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $this->_edit($fdata);
    }
    /**
     * 关闭
     * @return [type] [description]
     */
    public function set_return()
    {
        $rules = [
            ['field' => 'is_auto_return', 'label' => '开启自动退款', 'rules' => 'trim|required|in_list[0,1]'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $fdata['is_auto_return'] = (int) $fdata['is_auto_return'];
        $this->_edit($fdata);
    }
    /**
     * 编辑
     * @param  [type] $fdata [description]
     * @return [type]        [description]
     */
    private function _edit($fdata)
    {
        $fdata['update_time'] = time();
        $wmGzhConfigDao = WmGzhConfigDao::i($this->s_user->aid);
        $m_wm_gzh_config = $wmGzhConfigDao->getOne(['aid' => $this->s_user->aid]);
        if ($m_wm_gzh_config) {
            if ($wmGzhConfigDao->update($fdata, ['id' => $m_wm_gzh_config->id]) !== false) {
                 //清除缓存
            (new WmGzhConfigCache(['aid'=>$this->s_user->aid]))->delete();
                $this->json_do->set_msg('配置成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '配置信息失败');
            }

        } else {
            $fdata['aid'] = $this->s_user->aid;

            if ($wmGzhConfigDao->create($fdata)) {
                $this->json_do->set_msg('配置成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '配置信息失败');
            }

        }
    }
    /**
     * 公众号详情
     * @return [type] [description]
     */
    public function info()
    {
        $wmGzhConfigDao = WmGzhConfigDao::i($this->s_user->aid);
        $m_wm_gzh_config = $wmGzhConfigDao->getOne(['aid' => $this->s_user->aid]);
        $data['info'] = null;
        if ($m_wm_gzh_config) {
            $data['info'] = $m_wm_gzh_config;
        }
        $gzhInfo=null;
        try {
            $scrm_sdk = new scrm_sdk;
            $result = $scrm_sdk->getGzhInfo(['visit_id' => $this->s_user->visit_id]);
            if(isset($result['data']['authorizer_appid']))
            {
                //appid 需要重新赋值
                $gzhInfo['appid'] = $result['data']['authorizer_appid'];
                $gzhInfo['app_nick_name'] = $result['data']['nick_name'];
            }
            
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
        $data['gzh_info'] = $gzhInfo;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
