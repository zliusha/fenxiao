<?php

/**
 * @Author: binghe
 * @Date:   2018-06-12 14:16:08
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-12 17:04:59
 */
/**
 * 点餐小程序
 */
class Xcx_meal_config extends wm_service_controller
{
	
	public function __construct()
    {
        parent::__construct(module_enum::XCX_MEAL_MODULE);
    }
    public function index()
    {
        if (is_new_scrm()) {
            $erp_sdk = new erp_sdk;
            //[visit_id,user_id]
            $params = [$this->s_user->visit_id, $this->s_user->user_id];
            $result = $erp_sdk->getToken($params);
            $scrm_url = SCRM_URL . 'welcome_ydb/jump?token=' . $result['token'] . '&url=' . urlencode(SCRM_URL . 'wechat_oauth?useFor=4');
            $data['scrm_url'] = $scrm_url;

            $this->load->view('mshop/xcx_meal/index', $data);
        } else {
            $this->load->view('mshop/setting/update');
        }

    }
}