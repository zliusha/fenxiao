<?php

/**
 * @Author: binghe
 * @Date:   2018-06-12 14:15:48
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 13:27:21
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
/**
 * 点餐小程序
 */
class Xcx_config extends wm_service_controller
{
	
	public function __construct()
    {
        parent::__construct(module_enum::XCX_WM_MODULE);
    }
    public function index()
    {
        if (is_new_scrm()) {
            $erp_sdk = new erp_sdk;
            //[visit_id,user_id]
            $params = [$this->s_user->visit_id, $this->s_user->user_id];
            $result = $erp_sdk->getToken($params);
            $scrm_url = SCRM_URL . 'welcome_ydb/jump?token=' . $result['token'] . '&url=' . urlencode(SCRM_URL . 'wechat_oauth?useFor=5');
            $data['scrm_url'] = $scrm_url;

            $this->load->view('mshop/xcx/index', $data);
        } else {
            $this->load->view('mshop/setting/update');
        }

    }
    /**
     * 小程序装修
     * @return [type] [description]
     */
    public function banner()
    {
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $model = $mainCompanyAccountDao->getOne(['id' => $this->s_user->id], 'id,username,img,sex');
        $model->img = conver_picurl($model->img);
        $shop_model = null;
        if (!$this->is_zongbu) {
            $wmShopDao = WmShopDao::i($this->s_user->aid);
            $shop_model = $wmShopDao->getOne(['id' => $this->currentShopId]);
            $shop_model->shop_logo = conver_picurl($shop_model->shop_logo);
        }

        $this->load->view('mshop/xcx/banner', ['model' => $model, 'shop_model' => $shop_model]);
    }
}