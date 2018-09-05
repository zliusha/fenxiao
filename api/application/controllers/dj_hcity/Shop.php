<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午5:05
 */
use Service\Bll\Hcity\ShopBll;
use Service\Enum\SaasEnum;
use Service\Bll\Main\MainShopBll;
use Service\Bll\Main\MainShopAccountBll;

class Shop extends dj_hcity_controller
{

    /**
     * 获取门店列表
     * @author ahe<ahe@iyenei.com>
     */
    public function get_list()
    {
        $rules = [
            ['field' => 'hcity_show_status', 'label' => '商圈状态', 'rules' => 'trim'],
            ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim'],
            ['field' => 'contact', 'label' => '门店联系方式', 'rules' => 'trim'],
        ];

        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
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
        $list = (new ShopBll())->getShopList($this->s_user->aid, $data);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 获取门店详情
     * @author ahe<ahe@iyenei.com>
     */
    public function get_detail()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);

        $mainShopAccountBll = new MainShopAccountBll();
        $params = [
            'aid'=>$this->s_user->aid,
            'uid'=>$this->s_user->id,
            'is_admin'=>$this->s_user->is_admin
        ];
        $mainShopIds = $mainShopAccountBll->getShopIds($params);

        if (!$this->s_user->is_admin && !in_array($data['shop_id'], $mainShopIds)) {
            $this->json_do->set_error('005', '子账号没有此店铺管理权限');
        }
        $detail = (new ShopBll())->getShopDetail($this->s_user->aid, $data['shop_id']);
        $this->json_do->set_data($detail);
        $this->json_do->out_put();
    }

    /**
     * 创建门店
     * @author ahe<ahe@iyenei.com>
     */
    public function create()
    {
        if (!$this->s_user->is_admin) {
            $this->json_do->set_error('005', '请联系管理员创建门店');
        }
        $rules = [
            ['field' => 'category_ids', 'label' => '门店分类id', 'rules' => 'trim|required'],
            ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim|required'],
            ['field' => 'shop_logo', 'label' => '门店logo', 'rules' => 'trim|required'],
            ['field' => 'contact', 'label' => '联系方式', 'rules' => 'trim|required'],
            ['field' => 'shop_state', 'label' => '门店所在地-省份', 'rules' => 'trim|required'],
            ['field' => 'shop_city', 'label' => '门店所在地-城市', 'rules' => 'trim|required'],
            ['field' => 'shop_district', 'label' => '门店所在地-区域', 'rules' => 'trim|required'],
            ['field' => 'shop_address', 'label' => '门店所在地-详细地址', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim|required'],
            ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
            ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required'],
            ['field' => 'guest_unit_price', 'label' => '客单价', 'rules' => 'trim|required'],
            ['field' => 'on_time', 'label' => '营业时间', 'rules' => 'trim'],
            ['field' => 'shop_imgs', 'label' => '门店图片', 'rules' => 'trim'],
            ['field' => 'notice', 'label' => '公告', 'rules' => 'trim',"xss_clean"=>false],
        ];

        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        try {
            $data['saas_id'] = self::SAAS_ID;
            $ret = (new ShopBll())->createShop($this->s_user->aid, $data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '新增门店错误');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }


    /**
     * 删除门店
     * @author ahe<ahe@iyenei.com>
     */
    public function delete()
    {
        if (!$this->s_user->is_admin) {
            $this->json_do->set_error('005', '请联系管理员删除门店');
        }
        $rules = [
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);

        try {
            $data['saas_id'] = self::SAAS_ID;
            $ret = (new ShopBll())->deleteShop($this->s_user->aid, $data['shop_id'], $data['saas_id']);
            if ($ret > 0) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '删除失败');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 编辑门店
     * @author ahe<ahe@iyenei.com>
     */
    public function edit()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required'],
            ['field' => 'category_ids', 'label' => '门店分类id', 'rules' => 'trim|required'],
            ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim|required'],
            ['field' => 'shop_logo', 'label' => '门店logo', 'rules' => 'trim|required'],
            ['field' => 'contact', 'label' => '联系方式', 'rules' => 'trim|required'],
            ['field' => 'username', 'label' => '店铺负责人姓名', 'rules' => 'trim'],
            ['field' => 'shop_state', 'label' => '门店所在地-省份', 'rules' => 'trim|required'],
            ['field' => 'shop_city', 'label' => '门店所在地-城市', 'rules' => 'trim|required'],
            ['field' => 'shop_district', 'label' => '门店所在地-区域', 'rules' => 'trim|required'],
            ['field' => 'shop_address', 'label' => '门店所在地-详细地址', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim'],
            ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim'],
            ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim'],
            ['field' => 'guest_unit_price', 'label' => '客单价', 'rules' => 'trim'],
            ['field' => 'on_time', 'label' => '营业时间', 'rules' => 'trim'],
            ['field' => 'shop_imgs', 'label' => '门店图片', 'rules' => 'trim'],
            ['field' => 'notice', 'label' => '公告', 'rules' => 'trim',"xss_clean"=>false]
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        $mainShopAccountBll = new MainShopAccountBll();
        $params = [
            'aid'=>$this->s_user->aid,
            'uid'=>$this->s_user->id,
            'is_admin'=>$this->s_user->is_admin
        ];
        $mainShopIds = $mainShopAccountBll->getShopIds($params);
        if (!$this->s_user->is_admin && !in_array($data['shop_id'], $mainShopIds)) {
            $this->json_do->set_error('005', '子账号没有此店铺管理权限');
        }
        try {
            $data['saas_id'] = self::SAAS_ID;
            $ret = (new ShopBll())->editShop($this->s_user->aid, $data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '新增门店错误');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 申请入驻商圈
     * @author ahe<ahe@iyenei.com>
     */
    public function apply_hcity()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        $mainShopAccountBll = new MainShopAccountBll();
        $params = [
            'aid'=>$this->s_user->aid,
            'uid'=>$this->s_user->id,
            'is_admin'=>$this->s_user->is_admin
        ];
        $mainShopIds = $mainShopAccountBll->getShopIds($params);
        if (!$this->s_user->is_admin && !in_array($data['shop_id'], $mainShopIds)) {
            $this->json_do->set_error('005', '子账号没有此店铺管理权限');
        }
        try {
            $ret = (new ShopBll())->applyHcity($this->s_user->aid, $data['shop_id']);
            if ($ret > 0) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '申请失败');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 获取门店列表
     * @author feiying
     */
    public function getall_list()
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
        $list = (new MainShopBll())->all($this->s_user->aid,$data['saas_id']);
        if (!$this->s_user->is_admin) {
            $this->json_do->set_error('005', '子账号没有获取所有列表权限');
        }
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 获取门店列表（其他类型商圈）
     * @author feiying
     */
    public function all_create()
    {
        if (!$this->s_user->is_admin) {
            $this->json_do->set_error('005', '请联系管理员创建门店');
        }
        $rules = [
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|required'],
            ['field' => 'category_ids', 'label' => '门店分类id', 'rules' => 'trim|required'],
            ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim|required'],
            ['field' => 'shop_logo', 'label' => '门店logo', 'rules' => 'trim|required'],
            ['field' => 'contact', 'label' => '联系方式', 'rules' => 'trim|required'],
            ['field' => 'shop_state', 'label' => '门店所在地-省份', 'rules' => 'trim|required'],
            ['field' => 'shop_city', 'label' => '门店所在地-城市', 'rules' => 'trim|required'],
            ['field' => 'shop_district', 'label' => '门店所在地-区域', 'rules' => 'trim|required'],
            ['field' => 'shop_address', 'label' => '门店所在地-详细地址', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim|required'],
            ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
            ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required'],
            ['field' => 'guest_unit_price', 'label' => '客单价', 'rules' => 'trim|required'],
            ['field' => 'on_time', 'label' => '营业时间', 'rules' => 'trim'],
            ['field' => 'shop_imgs', 'label' => '门店图片', 'rules' => 'trim'],
            ['field' => 'notice', 'label' => '公告', 'rules' => 'trim'],
            ['field' => 'shop_extid', 'label' => '店铺扩展id', 'rules' => 'trim'],//如果有此字段，表示已经增加shopExt表
        ];

        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        try {
            $data['saas_id'] = self::SAAS_ID;
            if($data['shop_extid']){
               $ret = (new ShopBll())->editShop($this->s_user->aid, $data);
            } else {
               $ret = (new ShopBll())->allCreateShop($this->s_user->aid, $data); 
            }
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '新增门店错误');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    

}