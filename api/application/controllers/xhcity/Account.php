<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/11
 * Time: 下午2:06
 */
use Service\Cache\Hcity\HcityUserCache;
use Service\Cache\ApiAccessTokenCache;
use Service\Bll\Hcity\PayBll;
use Service\Bll\Hcity\AccountBll;
use Service\Bll\Hcity\ManageAccountBll;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityPaymentRecordDao;
use Service\Bll\Hcity\WithdrawalBll;
use Service\Support\FLock;
use Service\Cache\ApiRefreshTokenCache;

class Account extends xhcity_user_controller
{
    function __construct()
    {
        parent::__construct(['get_info_by_uid']);
    }

    /**
     * 获取个人信息
     * @author ahe<ahe@iyenei.com>
     */
    public function get_info()
    {
        $user = (new AccountBll())->getLoginUserInfo($this->s_user->uid);
        $this->json_do->set_data($user);
        $this->json_do->out_put();
    }

    /**
     * 获取个人中心主页简单会员信息
     * @author ahe<ahe@iyenei.com>
     */
    public function get_simple_info()
    {
        $user = (new AccountBll())->getUserSimpleInfo($this->s_user->uid);
        $this->json_do->set_data($user);
        $this->json_do->out_put();
    }

    /**
     * 修改个人信息
     * @author ahe<ahe@iyenei.com>
     */
    public function edit()
    {
        $rules = [
            ['field' => 'img', 'label' => '头像', 'rules' => 'trim'],
            ['field' => 'username', 'label' => '昵称', 'rules' => 'trim'],
            ['field' => 'inviter_code', 'label' => '邀请码', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        //校验用户状态
        $this->check_user_status();

        if (empty($fdata)) {
            $this->json_do->set_error('001');
        }
        try {
            (new AccountBll())->editUserInfo($this->s_user->uid, $fdata);
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

        if (!empty($fdata['img']) || !empty($fdata['username'])) {
            $this->s_user->headimg = !empty($fdata['img']) ? $fdata['img'] : $this->s_user->headimg;
            $this->s_user->nickname = !empty($fdata['username']) ? $fdata['username'] : $this->s_user->nickname;
            //4.1删除原登录信息
            $oldAccessToken = $this->input->get_request_header('Access-Token');
            $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $oldAccessToken]);
            $apiAccessTokenCache->delete();
            //4.2重新生成登录信息
            $newAccessToken = md5(create_guid());
            $newApiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $newAccessToken]);
            $expiredTime = 7200;
            $newApiAccessTokenCache->save($this->s_user, $expiredTime);

            //有效期为1天
            $refreshToken = md5(create_guid());
            $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
            $refreshExpiredTime = 3600 * 24;
            $apiRefreshTokenCache->save($this->s_user, $refreshExpiredTime);
            $time = time();
            //type = 0 临时用户,1正式用户
            $outData['access_token'] = ['value' => $newAccessToken, 'expire_time' => $time + $expiredTime];
            $outData['refresh_token'] = ['value' => $refreshToken, 'expire_time' => $time + $refreshExpiredTime];
            $outData['type'] = 1;    //用户标识-0游客,1正式
        } else {
            $outData = [];
        }

        $this->json_do->set_data($outData);
        $this->json_do->out_put();
    }

    /**
     * 修改手机号
     * @author ahe<ahe@iyenei.com>
     */
    public function changeMobile()
    {
        $rules = [
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'mobile_code', 'label' => '验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        //校验用户状态
        $this->check_user_status();

        //开放140注册
        if (substr($fdata['mobile'], 0, 3) == '140') {
            if ('123456' != $fdata['mobile_code'])
                $this->json_do->set_error('004', '验证码错误');
        } else {
            //验证码校验
            $mainMobileCodeDao = MainMobileCodeDao::i();
            $m_main_mobile_code = $mainMobileCodeDao->getNormalCode($fdata['mobile']);
            if (!$m_main_mobile_code || $m_main_mobile_code->code != $fdata['mobile_code']) {
                $this->json_do->set_error('004', '验证码错误');
            }
        }


        try {
            //保存手机号
            (new AccountBll())->editUserInfo($this->s_user->uid, $fdata);
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

        $this->s_user->mobile = $fdata['mobile'];

        //4.1删除原登录信息
        $oldAccessToken = $this->input->get_request_header('Access-Token');
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $oldAccessToken]);
        $apiAccessTokenCache->delete();
        //4.2重新生成登录信息
        $newAccessToken = md5(create_guid());
        $newApiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $newAccessToken]);
        $expiredTime = 7200;
        $newApiAccessTokenCache->save($this->s_user, $expiredTime);

        //有效期为1天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $refreshExpiredTime = 3600 * 24;
        $apiRefreshTokenCache->save($this->s_user, $refreshExpiredTime);
        $time = time();
        //type = 0 临时用户,1正式用户
        $outData['access_token'] = ['value' => $newAccessToken, 'expire_time' => $time + $expiredTime];
        $outData['refresh_token'] = ['value' => $refreshToken, 'expire_time' => $time + $refreshExpiredTime];
        $outData['type'] = 1;    //用户标识-0游客,1正式
        $this->json_do->set_data($outData);
        $this->json_do->out_put();
    }

    /**
     * 设置邀请人
     * @author ahe<ahe@iyenei.com>
     */
    public function set_inviter()
    {
        $rules = [
            ['field' => 'inviter_code', 'label' => '邀请码', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        //校验用户状态
        $this->check_user_status();

        if (empty($fdata)) {
            $this->json_do->set_error('001');
        }
        try {
            (new AccountBll())->setInviterByCode($this->s_user->uid, $fdata['inviter_code']);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 申请城市合伙人
     */
    public function apply_manage()
    {
        $rules = [
            ['field' => 'mobile', 'label' => '手机号码', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'username', 'label' => '姓名', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim|required'],
            ['field' => 'shop_state', 'label' => '省份', 'rules' => 'trim|required'],
            ['field' => 'shop_city', 'label' => '城市', 'rules' => 'trim|required'],
            ['field' => 'shop_district', 'label' => '区域', 'rules' => 'trim'],//可以为空
//            ['field' => 'mobile_code', 'label' => '验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        //校验用户状态
        $this->check_user_status();

//        $mainMobileCodeDao = MainMobileCodeDao::i();
//        $mMainMobileCodeDao = $mainMobileCodeDao->getNormalCode($fdata['mobile']);
//        if (!$mMainMobileCodeDao || $mMainMobileCodeDao->code != $fdata['mobile_code']) {
//            $this->json_do->set_error('004', '验证码错误');
//        }

        $fdata['uid'] = $this->s_user->uid;

        $manageAccountBll = new ManageAccountBll();
        $ret = $manageAccountBll->applyManageAccount($fdata);

        if ($ret > 0) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '申请失败');
        }
    }

    /**
     * 获取我的团队成员
     * @author ahe<ahe@iyenei.com>
     */
    public function get_team_list()
    {
        try {
            $list = (new AccountBll())->getTeamList($this->s_user->uid);
            $this->json_do->set_data($list);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 获取我的邀请商家
     * @author ahe<ahe@iyenei.com>
     */
    public function get_merchants()
    {
        try {
            $list = (new AccountBll())->getMerchants($this->s_user->uid);
            $this->json_do->set_data($list);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 用户充值
     * @liusha
     */
    public function recharge()
    {
        if (!FLock::getInstance()->lock(__METHOD__ . $this->s_user->uid)) {
            $this->json_do->set_error('005', '操作过于频繁,请稍后再试。');
        }
        $rules = [
            ['field' => 'money', 'label' => '金额', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        //校验用户状态
        $this->check_user_status();

        //限制充值金额 10万
        if ($fdata['money'] > 100000) {
            throw new Exception("充值金额过大");
        }

        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $data['uid'] = $this->s_user->uid;
        $data['aid'] = 0;
        $data['shop_id'] = 0;
        $data['type'] = 3;
        $data['pay_type'] = 3;
        $data['pay_source'] = 'xcx';
        $data['source_type'] = 0;
        $data['money'] = $fdata['money'];
        $data['pay_tid'] = create_order_number();
        $hcityPaymentRecordDao->create($data);

        $payBll = new PayBll();
        $params = $payBll->wxJsapiPay($data['uid'], $this->s_user->openid, $data['pay_tid']);

        $ret['params'] = $params;
        $ret['pay_tid'] = $data['pay_tid']; // 支付订单号
        $ret['pay_type'] = 3;
        $ret['server_time'] = date('Y-m-d H:i:s'); // 服务器时间
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 开卡/续费
     * @liusha
     */
    public function apply_hcrad()
    {
        if (!FLock::getInstance()->lock(__METHOD__ . $this->s_user->uid)) {
            $this->json_do->set_error('005', '操作过于频繁,请稍后再试。');
        }
        $rules = [
            ['field' => 'source_type', 'label' => '开卡入口', 'rules' => 'trim|numeric'],
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        //校验用户状态
        $this->check_user_status();

        //加载配置
        $xcx_inc = &inc_config('xcx_hcity');

        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $data['uid'] = $this->s_user->uid;
        $data['aid'] = 0;
        $data['shop_id'] = 0;
        $data['type'] = 2;
        $data['pay_type'] = 3;
        $data['pay_source'] = 'xcx';
        $data['source_type'] = $fdata['source_type'];
        $data['money'] = $xcx_inc['hcard_money'];
        $data['pay_tid'] = create_order_number();
        //验证一店一码门店
        if ($fdata['source_type'] == 2 && empty($fdata['shop_id'])) {
            $this->json_do->set_error('005', '请选择门店');
        }
        //如果shop>0 获取aid
        if ($fdata['shop_id'] > 0) {
            $shop = MainShopDao::i()->getOne(['id' => $fdata['shop_id']]);
            if (!$shop)
                $this->json_do->set_error('005', '门店不存在');
            $data['aid'] = $shop->aid;
            $data['shop_id'] = $shop->id;
        }
        $hcityPaymentRecordDao->create($data);

        $payBll = new PayBll();
        $params = $payBll->wxJsapiPay($data['uid'], $this->s_user->openid, $data['pay_tid']);

        $ret['params'] = $params;
        $ret['pay_tid'] = $data['pay_tid']; // 支付订单号
        $ret['pay_type'] = 3;
        $ret['server_time'] = date('Y-m-d H:i:s'); // 服务器时间
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 申请提现
     * @author ahe<ahe@iyenei.com>
     */
    public function apply_withdrawal()
    {
        if (!FLock::getInstance()->lock(__METHOD__ . $this->s_user->uid)) {
            //取锁失败
            $this->json_do->set_error('005', '小程序提现过于频繁，请稍后再试');
        }
        $rules = [
            ['field' => 'money', 'label' => '提现金额', 'rules' => 'trim|required|numeric'],
            ['field' => 'bind_card_id', 'label' => '银行卡id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        //校验用户状态
        $this->check_user_status();

        $ret = (new WithdrawalBll())->apply($this->s_user, $fdata);

        //删除缓存
        (new HcityUserCache(['uid' => $this->s_user->uid]))->delete();

        if ($ret) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '提现失败');
        }
    }

    /**
     * 绑定银行卡
     * @author ahe<ahe@iyenei.com>
     */
    public function bind_bank_card()
    {
        $rules = [
            ['field' => 'user_name', 'label' => '收款人姓名', 'rules' => 'trim|required'],
            ['field' => 'bank_card_number', 'label' => '银行卡号', 'rules' => 'trim|required'],
            ['field' => 'bank_name', 'label' => '银行名称', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        //校验用户状态
        $this->check_user_status();

        $ret = (new AccountBll())->bindBankCard($this->s_user, $fdata);
        if ($ret) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '绑定失败');
        }
    }

    /**
     * 修改银行卡信息
     * @author ahe<ahe@iyenei.com>
     */
    public function edit_bank_card()
    {
        $rules = [
            ['field' => 'bind_card_id', 'label' => '银行卡id', 'rules' => 'trim|required|numeric'],
            ['field' => 'user_name', 'label' => '收款人姓名', 'rules' => 'trim|required'],
            ['field' => 'bank_card_number', 'label' => '银行卡号', 'rules' => 'trim|required'],
            ['field' => 'bank_name', 'label' => '银行名称', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        //校验用户状态
        $this->check_user_status();

        $ret = (new AccountBll())->editBankCard($this->s_user, $fdata);
        if ($ret) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '修改失败');
        }
    }

    /**
     * 获取默认银行卡
     * @author ahe<ahe@iyenei.com>
     */
    public function get_default_bank_card()
    {
        $list = (new AccountBll())->getDefaultBankCard($this->s_user->uid);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 升级成为网点合伙人
     * @author ahe<ahe@iyenei.com>
     */
    public function upgrade_website_user()
    {
        //校验用户状态
        $this->check_user_status();

        $list = (new AccountBll())->upgradeWebsiteUser($this->s_user->uid);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 一元体验30天嘿卡会员
     * @author ahe<ahe@iyenei.com>
     */
    public function trial_hcard()
    {
        if (!FLock::getInstance()->lock(__METHOD__ . $this->s_user->uid)) {
            $this->json_do->set_error('005', '操作过于频繁,请稍后再试。');
        }

        //校验用户状态
        $this->check_user_status();
        //检测是否已开通过嘿卡或已开通过体验
        $user = (new AccountBll())->getLoginUserInfo($this->s_user->uid);
        if ($user->is_open_hcard == 1) {
            $this->json_do->set_error('005', '您已开通嘿卡，无法继续体验嘿卡会员');
        }
        if ($user->is_trial_hcard == 1) {
            $this->json_do->set_error('005', '您已是嘿卡体验会员，无法继续体验。');
        }

        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $data['uid'] = $this->s_user->uid;
        $data['aid'] = 0;
        $data['shop_id'] = 0;
        $data['type'] = 5;
        $data['pay_type'] = 3;
        $data['pay_source'] = 'xcx';
        $data['source_type'] = 1;
        $data['money'] = 1;//一元体验
        $data['pay_tid'] = create_order_number();
        $hcityPaymentRecordDao->create($data);

        $payBll = new PayBll();
        $params = $payBll->wxJsapiPay($data['uid'], $this->s_user->openid, $data['pay_tid']);

        $ret['params'] = $params;
        $ret['pay_tid'] = $data['pay_tid']; // 支付订单号
        $ret['pay_type'] = 3;
        $ret['server_time'] = date('Y-m-d H:i:s'); // 服务器时间
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 通过会员ID，获取会员信息
     * @author ahe<ahe@iyenei.com>
     */
    public function get_info_by_uid()
    {
        $rules = [
            ['field' => 'uid', 'label' => '会员id', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $user = (new AccountBll())->getLoginUserInfo($fdata['uid']);
        $this->json_do->set_data($user);
        $this->json_do->out_put();
    }
}