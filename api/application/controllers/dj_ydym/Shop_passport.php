<?php

/**
 * @Author: binghe
 * @Date:   2018-07-24 11:37:54
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 16:43:44
 */
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Bll\Hcity\ShopBll;
use Service\Bll\Main\MainShopAccountBll;
/**
 * 门店登录
 */
class Shop_passport extends dj_ydym_controller
{

    /**
     * 店铺切换
     * @param  [type] $shopId [description]
     * @return [type]         [description]
     */
    public function toggle()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $shopId = $fdata['shop_id'];

        //1.获取mainShopIds,总账号为空
        $mainShopAccountBll = new MainShopAccountBll();
        $params = [
            'aid'=>$this->s_user->aid,
            'uid'=>$this->s_user->id,
            'is_admin'=>$this->s_user->is_admin
        ];
        $mainShopIds = $mainShopAccountBll->getShopIds($params);

        if(!$this->s_user->is_admin && empty($mainShopIds))
            $this->json_do->set_error('004', '子账号没有总部权限');

        $currentShopId = 0;
        //null时自动登录上一次店铺，上一次店铺不存在，则登录第一个店铺
        if ($shopId === null) {
            $lastShopId = get_secret_cookie('c_ydym_shop_id',$this->s_user->id);
            if($lastShopId !== null)
                $currentShopId = $lastShopId;
            elseif(!$this->s_user->is_admin)    //子账号默认取第一个门店
                $currentShopId = $mainShopIds[0];
        } 
        else
            $currentShopId = $shopId;
        
        if ($currentShopId > 0) {

            if ($this->s_user->is_admin) {
                $mainShopDao = MainShopDao::i();
                $mMainShop = $mainShopDao->getOne(['aid' => $this->s_user->aid, 'id' => $currentShopId]);
                if (!$mMainShop)
                    $this->json_do->set_error('004', '没有权限');
            } elseif (!in_array($currentShopId, $mainShopIds))
                $this->json_do->set_error('004', '子账号没有门店管理权限');

        } else {
            if (!$this->s_user->is_admin)
                $this->json_do->set_error('004', '子账号没有总部权限');
        }
        $data['current_shop_id'] = $currentShopId;
        $data['is_zongbu'] = $currentShopId == 0;
        $data['is_admin'] = $this->s_user->is_admin;
        //设置永久cookie,成功时直接跳转
        set_secret_cookie('c_ydym_shop_id', $currentShopId, 3600 * 24 * 7,$this->s_user->id);
        $this->json_do->set_data($data);
        $this->json_do->set_msg('切换门店成功');
        $this->json_do->out_put();
    }

    /**
     * 获取所有许可的店铺列表
     * @author ahe<ahe@iyenei.com>
     */
    public function get_shop_list()
    {
        $data['saas_id'] = self::SAAS_ID;
        $data['is_admin'] = $this->s_user->is_admin;

        $mainShopAccountBll = new MainShopAccountBll();
        $params = [
            'aid'=>$this->s_user->aid,
            'uid'=>$this->s_user->id,
            'is_admin'=>$this->s_user->is_admin
        ];
        $mainShopIds = $mainShopAccountBll->getShopIds($params);

        $data['main_shop_ids'] = $mainShopIds;
        $list['rows'] = (new ShopBll())->getAllAllowShop($this->s_user->aid, $data);
        $list['current_shop_id'] = $this->currentShopId;
        $list['is_zongbu'] = $this->isZongbu;
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }
}