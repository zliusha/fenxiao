<?php
defined('BASEPATH') or exit('No direct script access allowed');
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
/**
 * 系统设置
 */
class Setting extends wm_service_controller
{
    public function account()
    {
        $data['aid'] = $this->s_user->aid;
        $this->load->view('mshop/setting/account', $data);
    }

    public function wechat()
    {
        $data['domain'] = $this->get_shop_base_url($scheme = null,false);
        $data['url_scheme'] = URL_SCHEME;

        //公众号授权地址
        $erp_sdk = new erp_sdk;
        //[visit_id,user_id]
        $params = [$this->s_user->visit_id, $this->s_user->user_id];
        $result = $erp_sdk->getToken($params);
        $scrm_url = SCRM_URL . 'welcome_ydb/jump?token=' . $result['token'] . '&url=' . urlencode(SCRM_URL . 'wechat_oauth');
        $data['scrm_url'] = $scrm_url;
        $this->load->view('mshop/setting/wechat', $data);
    }

    public function delivery_dianwoda()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 判断子账号权限
        if ($this->is_zongbu) {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'is_delete' => 0]); // 门店列表
        } else {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId, 'is_delete' => 0]); // 门店列表
        }

        $this->load->view('mshop/setting/delivery_dianwoda', $data);
    }

    public function delivery_cantingbao()
    {
        $data['notify_url'] = DJADMIN_URL . 'notify_cantingbao/index/'.$this->s_user->aid;

        $this->load->view('mshop/setting/delivery_cantingbao', $data);
    }
}
