<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 系统设置
 */
use Service\Cache\WmGzhConfigCache;
use Service\DbFrame\DataBase\WmMainDbModels\WmSettingDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
class Setting_api extends wm_service_controller
{
    /**
     * 获取商城设置
     */
    public function get_mall_setting()
    {
        $wmSettingDao = WmSettingDao::i();
        $model = $wmSettingDao->getOne(['aid' => $this->s_user->aid]);
        if (!$model) {
            $model = new stdClass();
            $model->share_img = '';
            $model->share_desc = '';
            $model->domain = '';
            $model->aid = $this->s_user->aid;
        }

        $wmGzhConfigCache = new WmGzhConfigCache(['aid' => $this->s_user->aid]);
        $config = $wmGzhConfigCache->getDataASNX();

        //开启第三方授权登录
        if ($config->login_type == 1) {
            $scrm_sdk = new scrm_sdk;
            $result = $scrm_sdk->getGzhInfo(['visit_id' => $this->s_user->visit_id]);
            $model->domain = $result['data']['authorizer_appid'];
        } else {
            $model->domain = '';
            $wm_setting = $wmSettingDao->getOne(['aid' => $this->s_user->aid]);

            if ($wm_setting && !empty($wm_setting->domain)) {
                $model->domain = $wm_setting->domain;
            }
        }

        $this->json_do->set_data($model);
        $this->json_do->out_put();
    }

    /**
     * 保存商城设置
     */
    public function save_mall_setting()
    {
        $rule = [
            ['field' => 'share_img', 'label' => '分享图片', 'rules' => 'trim|required'],
            ['field' => 'share_title', 'label' => '分享标题', 'rules' => 'trim|required'],
            ['field' => 'share_desc', 'label' => '分享描述', 'rules' => 'trim|required'],
            ['field' => 'domain', 'label' => 'domain', 'rules' => 'trim|preg_key[LETTERNUM]|min_length[4]|max_length[20]'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $wmSettingDao = WmSettingDao::i();
        //验证域名
        if($fdata['domain'])
        {
            $fdata['domain'] = strtolower($fdata['domain']);
            //判断域名是否重复
            $m_model = $wmSettingDao->getOne("aid != {$this->s_user->aid} AND domain='{$fdata['domain']}'");
            if ($m_model) {
                $this->json_do->set_error('001', '域名重复');
            }

            $wmGzhConfigCache = new WmGzhConfigCache(['aid' => $this->s_user->aid]);
            $config = $wmGzhConfigCache->getDataASNX();

            //开发模式
            if($config->login_type == 0)
            {
                if(is_wx_appid($fdata['domain'])) {
                    $this->json_do->set_error('001', '自定义域名不能以wx作为开头，如需修改，请联系我们');
                }
            }
        }

        $model = $wmSettingDao->getOne(['aid' => $this->s_user->aid]);
        if (!$model) //不存在创建，存在更新
        {
            $fdata['aid'] = $this->s_user->aid;
            $id = $wmSettingDao->create($fdata);
            if ($id > 0) {
                $this->json_do->set_data('保存成功');
                $this->json_do->out_put();
            }
        } else {
            if ($wmSettingDao->update($fdata, ['id' => $model->id]) !== false) {
                $this->json_do->set_data('保存成功');
                $this->json_do->out_put();
            }
        }
        $this->json_do->set_error('005', '保存失败');
    }

    /**
     * 读取配送方式
     */
    public function shipping_method()
    {
        $mainCompanyDao = MainCompanyDao::i();
        $m_main_company = $mainCompanyDao->getOne(['id' => $this->s_user->aid]);

        $this->json_do->set_data($m_main_company);
        $this->json_do->out_put();
    }

    /**
     * 更新配送方式
     */
    public function update_shipping_method()
    {
        $rule = [
            ['field' => 'shipping', 'label' => '配送方式', 'rules' => 'trim|numeric|required'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $mainCompanyDao = MainCompanyDao::i();

        if($fdata['shipping'] == 5)//风达配送，验证是否绑定代理商
        {
            try{
                $ci_fengda = new ci_fengda();

                $params['merchantId'] = $this->s_user->aid;
                $result = $ci_fengda->request($params, ci_fengda::AGENT_INFO);

            }catch(Exception $e) {
                $errMsg = $e->getMessage();
                log_message('error', __METHOD__ . '--' . "风达获取代理商失败-" . $errMsg);
                $this->json_do->set_error('005',$errMsg);
            }
        }

        if ($mainCompanyDao->update(['shipping' => $fdata['shipping']], ['id' => $this->s_user->aid]) !== false) {

            $this->json_do->set_data('保存成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_data('保存失败');
            $this->json_do->out_put();
        }
    }
}
