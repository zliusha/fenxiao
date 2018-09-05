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
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityPaymentRecordDao;
use Service\Bll\Hcity\PayBll;
use Service\Bll\Main\MainShopAccountBll;
class Shop extends dj_ydym_controller
{

    /**
     * 获取门店列表
     * @author ahe<ahe@iyenei.com>
     */
    public function get_list()
    {
        $rules = [
            ['field' => 'barcode_status', 'label' => '一店一码状态', 'rules' => 'trim'],
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
                $out['shop_id'] = $ret;
                $this->json_do->set_data($out);
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
     * 获取门店列表（其他类型加入商圈）
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
     * 获取门店列表（其他类型加入一店一码）
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

    /**
     * 一店一码支付
     * @author feiying
     */
    public function apply_ydym()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|numeric|required']
        ];
        $this->check->check_ajax_form($rules);
        if (!$this->s_user->is_admin) {
            $this->json_do->set_error('005', '子账号没有添加一店一码权限');
        }
        $fdata = $this->form_data($rules);
        $shop = MainShopDao::i()->getOne(['id'=>$fdata['shop_id'], 'aid'=>$this->s_user->aid]);
        if(!$shop)
            $this->json_do->set_error('005', '门店不存在');

        //加载配置
        $xcx_inc = &inc_config('xcx_hcity');

        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $data['aid'] = $this->s_user->aid;
        $data['shop_id'] = $fdata['shop_id'];
        $data['type'] = 4;
        $data['pay_type'] = 3;
        $data['pay_source'] = 'ydym';
        $data['source_type'] = 0;
        $data['money'] = $xcx_inc['ydym_money'];
        $data['pay_tid'] = create_order_number();
        $hcityPaymentRecordDao->create($data);
        $payBll = new PayBll();
        $params = $payBll->wxNativePay((int)$fdata['shop_id'],$data['pay_tid']);
        $ret['params'] = $params;
        $ret['pay_tid'] = $data['pay_tid']; // 支付订单号
        $ret['pay_type'] = 3;
        $ret['server_time'] = date('Y-m-d H:i:s'); // 服务器时间
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 判断订单是否支付
     * @author feiying
     */
    public function pay_status()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|numeric|required'],
            ['field' => 'pay_tid', 'label' => '门店ID', 'rules' => 'trim|numeric|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        if (!$this->s_user->is_admin) {
            $this->json_do->set_error('005', '子账号没有此权限');
        }
        $payInfo = [
            'shop_id' => $fdata['shop_id'],
            'pay_tid' => $fdata['pay_tid'],
            'status'  => 1,
        ];
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i(['aid' =>$this->s_user->aid ]);
        $data = $hcityPaymentRecordDao->getOne($payInfo);
        if($data){
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '订单暂未支付');
        }     
    }

}