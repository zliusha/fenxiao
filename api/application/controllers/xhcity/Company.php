<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/24
 * Time: 17:45
 */
use Service\Enum\SaasEnum;
use Service\Bll\Hcity\PayBll;
use Service\Bll\Hcity\AccountBll;
use Service\Bll\Hcity\CompanyExtBll;
use Service\Bll\Main\MainSaasAccountBll;
use Service\Bll\Hcity\ShopBll;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityPaymentRecordDao;

class Company extends xhcity_user_controller
{

    /**
     * 注册，不会自动登录
     * @return [type] [description]
     * @liusha
     */
    public function register()
    {
        $rule=[
            ['field' => 'username', 'label' => '用户名', 'rules' => 'trim|required'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|preg_key[PWD]'],
            ['field' => 'repassword', 'label' => '确认密码', 'rules' => 'trim|required|preg_key[PWD]|matches[password]'],
            ['field' => 'mobile_code', 'label' => '验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]'],
            ['field' => 'inviter_code','label'=>'邀请码','rules'=>'trim']
        ];
        $this->check->check_form($rule);
        $fdata=$this->form_data($rule);

        $user = HcityUserDao::i()->getOne(['id'=>$this->s_user->uid]);
        if(!empty($user->aid))
            $this->json_do->set_error('004','已注册商家');

        $mobile = $this->s_user->mobile;
        //开放140注册
        if(substr($mobile, 0,3)=='140')
        {
            if('123456'!=$fdata['mobile_code'])
                $this->json_do->set_error('004','验证码错误');
        }
        else
        {
            //验证手机号验证码
            $mainMobileCodeDao = MainMobileCodeDao::i();
            $m_main_mobile_code=$mainMobileCodeDao->getRegCode($mobile);
            if(!$m_main_mobile_code || $m_main_mobile_code->code!=$fdata['mobile_code'])
                $this->json_do->set_error('004','验证码错误');
        }
        $erp_sdk = new erp_sdk();
        try {
            //查询是否已注册
            $_params[] = $mobile;
            $res = $erp_sdk->getUserByPhone($_params);
            if (empty($res)) {
                try {
                    //未注册->注册商家
                    $params[] = $mobile;
                    $params[] = $fdata['password'];
                    $ip = get_ip();
                    $params[] = ['ip' => $ip, 'user_name' => $fdata['username'], 'source' => 'ydb', 'appFrom' => 'ydb'];
                    $res = $erp_sdk->register($params);
                } catch (Exception $e) {
                    $this->json_do->set_error('005', $e->getMessage());
                }
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
        $r_params['res_info']=$res;

        $mainCompanyDao = MainCompanyDao::i();
        //判断是否已注册商家
        $mainCompany =$mainCompanyDao->getOne(['visit_id'=>$res['visit_id']]);
        
        if($r_params['res_info']['user_nature'] !=1)
        {
            $this->json_do->set_error('004','账号已存在，请使用主账号分配店铺');
        }
        if($mainCompany || $mainCompanyDao->register($r_params))
        {
            ///重新获取用户信息,并自动登录
            $mainCompanyAccountDao = MainCompanyAccountDao::i();
            $m_main_company_account = $mainCompanyAccountDao->getOne(['visit_id'=>$res['visit_id'],'user_id'=>$res['user_id']]);
            if(!$m_main_company_account)
                $this->json_do->set_error('004','账号注册失败');
            //更新user表aid
            HcityUserDao::i()->update(['aid'=>$m_main_company_account->aid], ['id'=>$this->s_user->uid]);

            $data['aid']=$m_main_company_account->aid;
            $data['uid']=$res['user_id'];
            $data['visit_id']=$res['visit_id'];
            //创建邀请关系
            try {
                $inviterCode = empty($fdata['inviter_code']) ? '' : $fdata['inviter_code'];
                (new CompanyExtBll())->setInviterByCode($data, $inviterCode);
            } catch (Exception $e) {
                log_message('error', __METHOD__ . '设置邀请码失败：' . json_encode($data));
            }

            $this->json_do->set_msg('注册成功');
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005','注册失败,请重新注册');
    }

    /**
     * 获取所有商家店铺列表
     * @liusha
     */
    public function get_shop_list()
    {
        $user = HcityUserDao::i()->getOne(['id'=>$this->s_user->uid]);
        if(empty($user->aid))
            $this->json_do->set_error('004','请先注册商家');

        $mainShopDao = MainShopDao::i();
        $shop_list = $mainShopDao->getAllArray(['aid'=>$user->aid], 'id');
        $shop_ids  = implode(',', array_column($shop_list, 'id'));

        $shopBll = new ShopBll();
        $data['rows'] = $shopBll->getShopByIds($shop_ids);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 创建门店
     * @author liusha<liusha@iyenei.com>
     */
    public function shop_add()
    {
        $user = HcityUserDao::i()->getOne(['id'=>$this->s_user->uid]);
        if(empty($user->aid))
            $this->json_do->set_error('004','请先注册商家');
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
            ['field' => 'saas_id', 'label' => '店铺类型', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $fdata['guest_unit_price'] = 0;
        $fdata['on_time'] = '';
        $fdata['shop_imgs']= '';
        $fdata['notice'] = '';
        try {
            $shopBll = new ShopBll();
            $ret = $shopBll->createShop($user->aid, $fdata);
            if ($ret) {
                $data = $shopBll->getShopDetail($user->aid, $ret);
                $this->json_do->set_data($data);
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
     * @liusha
     */
    public function apply_sq()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $user = HcityUserDao::i()->getOne(['id'=>$this->s_user->uid]);
        if(empty($user->aid))
            $this->json_do->set_error('004','请先注册商家');
        //判断是否是当前商家店铺
        $shopBll = new ShopBll();
        $shop = $shopBll->getShopDetail($user->aid, $fdata['shop_id']);
        if(!$shop['shop_ext'])
            $this->json_do->set_error('005', '门店不存在');
        //判断门店状态
        if($shop['shop_ext']->hcity_show_status==1)
            $this->json_do->set_error('005', '门店已入住');
        //如果门店不是商圈，判断不是待审核状态则都可以申请
        if($shop['shop_ext']->hcity_audit_status==1)
            $this->json_do->set_error('005', '门店已申请');
        HcityShopExtDao::i()->update(['hcity_audit_status'=>1, 'hcity_apply_time'=>time()],['shop_id'=>$fdata['shop_id'], 'aid'=>$user->aid]);

        //判断是否存在账户关系,增加商家ydym Saas关系
        $mainSaasAccountDao = MainSaasAccountDao::i();
        $mSaasAccount = $mainSaasAccountDao->getOne(['aid'=>$user->aid, 'saas_id'=>SaasEnum::HCITY]);
        if(!$mSaasAccount)
        {
            (new MainSaasAccountBll())->register([
                'aid' => $user->aid,
                'saas_id' => SaasEnum::HCITY,
                'update_time' => time(),
                'time' => time()
            ]);
        }
        $this->json_do->set_msg('申请成功');
        $this->json_do->out_put();
    }

    /**
     * 开启一店一码
     * @liusha
     */
    public function apply_ydym()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        //判断是否注册商家
        $user = (new AccountBll())->getLoginUserInfo($this->s_user->uid);
        if(!($user->aid>0))
            $this->json_do->set_error('005', '请先注册商家');
        //判断是否是当前商家店铺
        $shop = MainShopDao::i()->getOne(['id'=>$fdata['shop_id'], 'aid'=>$user->aid]);
        if(!$shop)
            $this->json_do->set_error('005', '门店不存在');

        //加载配置
        $xcx_inc = &inc_config('xcx_hcity');

        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $data['uid'] = $this->s_user->uid;
        $data['aid'] = $user->aid;
        $data['shop_id'] = $fdata['shop_id'];
        $data['type'] = 4;
        $data['pay_type'] = 3;
        $data['pay_source'] = 'xcx';
        $data['source_type'] = 0;
        $data['money'] = $xcx_inc['ydym_money'];
        $data['pay_tid'] = create_order_number();
        $hcityPaymentRecordDao->create($data);

        $payBll = new PayBll();
        $params = $payBll->wxJsapiPay($data['uid'], $this->s_user->openid, $data['pay_tid']);

        //判断是否存在账户关系,增加商家ydym Saas关系
        $mainSaasAccountDao = MainSaasAccountDao::i();
        $mSaasAccount = $mainSaasAccountDao->getOne(['aid'=>$user->aid, 'saas_id'=>SaasEnum::YDYM]);
        if(!$mSaasAccount)
        {
            (new MainSaasAccountBll())->register([
                'aid' => $user->aid,
                'saas_id' => SaasEnum::HCITY,
                'update_time' => time(),
                'time' => time()
            ]);
        }


        $ret['params'] = $params;
        $ret['pay_tid'] = $data['pay_tid']; // 支付订单号
        $ret['pay_type'] = 3;
        $ret['server_time'] = date('Y-m-d H:i:s'); // 服务器时间
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }
}