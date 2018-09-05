<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午5:06
 */
use Service\Enum\SaasEnum;
use Service\Cache\Hcity\HcityShopExtCache;

/**
 * 商圈基类
 */
class dj_hcity_controller extends dj_controller
{
    const SAAS_ID = SaasEnum::HCITY;
    //当前所选的门店,0代表综合门店
    public $currentShopId = null;
    //是否总部
    public $isZongbu = 0;

    public function __construct()
    {
        parent::__construct();
        $this->validSaas(self::SAAS_ID);
        $this->_load_shops();
        if ($this->currentShopId === null) {
            $noAuth = ['shop_passport'];
            if (!in_array($this->url_class, $noAuth))
                $this->json_do->set_error('004', '未选择门店');
        }
    }

    //自动加载店铺
    private function _load_shops()
    {
        $currentShopId = get_secret_cookie('c_hcity_shop_id', $this->s_user->id);
        if ($currentShopId === null)
            return;
        if (!is_numeric($currentShopId)) {
            delete_cookie('c_hcity_shop_id');
            return;
        }
        $currentShopId = (int)$currentShopId;
        //子账号不能有总部管理权限
        if (!$this->s_user->is_admin && $currentShopId == 0) {
            delete_cookie('c_hcity_shop_id');
            return;
        }
        $this->currentShopId = $currentShopId;
        if ($this->currentShopId == 0)
            $this->isZongbu = 1;
    }

    /**
     * 验证商圈店铺有效性
     * @author ahe<ahe@iyenei.com>
     */
    protected function validHcityShop()
    {
        if (empty($this->currentShopId)) {
            $this->json_do->set_error('004', '总店禁止此操作');
        }
        $shopExtCache = new HcityShopExtCache(['shop_id' => $this->currentShopId]);
        $shop = $shopExtCache->getDataASNX();
        if ($shop['hcity_show_status'] == 0) {
            $this->json_do->set_error('004', '当前店铺未入驻商圈，请先免费申请入驻商圈！');
        }
        if ($shop['hcity_show_status'] == 2) {
            $this->json_do->set_error('004', '当前店铺已被商圈管理员清退！');
        }
    }

    /**
     * 验证一店一码店铺有效性
     * @author ahe<ahe@iyenei.com>
     */
    protected function validYdymShop()
    {
        if (empty($this->currentShopId)) {
            $this->json_do->set_error('004', '总店禁止此操作');
        }
        $shopExtCache = new HcityShopExtCache(['shop_id' => $this->currentShopId]);
        $shop = $shopExtCache->getDataASNX();
        if ($shop['barcode_status'] == 0) {
            $this->json_do->set_error('004', '当前店铺未开通一店一码，请先开通！');
        }
        if ($shop['barcode_expire_time'] < time()) {
            $this->json_do->set_error('004', '当前店铺一店一码已过期，请先续费');
        }
    }
}