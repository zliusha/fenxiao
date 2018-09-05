<?php

/**
 * @Author: binghe
 * @Date:   2018-06-14 13:54:40
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:48
 */
use Service\Cache\WmAlipayConfigCache;

use Service\DbFrame\DataBase\WmShardDbModels\WmAlipayConfigDao;
/**
 * 阿里支付配置
 */
class Alipay_config_api extends wm_service_controller
{
	
	/**
     * 支付配置
     * @return [type] [description]
     */
    public function pay_edit()
    {
        $rules = [
        	['field' => 'app_id', 'label' => '应用ID', 'rules' => 'trim|required'],
        	['field' => 'sign_type', 'label' => '密钥类型', 'rules' => 'trim|required|in_list[1,2]'],
            ['field' => 'alipay_public_key', 'label' => '支付宝公钥', 'rules' => 'trim|required'],
            ['field' => 'merchant_private_key', 'label' => '商户私钥', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $this->_edit($fdata);
    }
    /**
     * 编辑
     * @param  [type] $fdata [description]
     * @return [type]        [description]
     */
    private function _edit($fdata)
    {
        $wmAlipayConfigDao = WmAlipayConfigDao::i($this->s_user->aid);
        $m_alipay_gzh_config = $wmAlipayConfigDao->getOne(['aid' => $this->s_user->aid]);

        if ($m_alipay_gzh_config) {

            if ($wmAlipayConfigDao->update($fdata, ['id' => $m_alipay_gzh_config->id]) !== false) {
            	//清除缓存
        		(new WmAlipayConfigCache(['aid'=>$this->s_user->aid]))->delete();
                $this->json_do->set_msg('配置成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '配置信息失败');
            }

        } else {
            $fdata['aid'] = $this->s_user->aid;

            if ($wmAlipayConfigDao->create($fdata)) {
                $this->json_do->set_msg('配置成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '配置信息失败');
            }

        }
    }
    /**
     * 详情
     * @return [type] [description]
     */
    public function info()
    {

        $wmAlipayConfigDao = WmAlipayConfigDao::i($this->s_user->aid);
        $m_wm_alipay_config = $wmAlipayConfigDao->getOne(['aid' => $this->s_user->aid]);
        $data['info'] = null;
        if ($m_wm_alipay_config) {
            $data['info'] = $m_wm_alipay_config;
        }
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}