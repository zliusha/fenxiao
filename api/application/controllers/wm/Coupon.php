<?php
use Service\Cache\WmGzhConfigCache;

use Service\DbFrame\DataBase\WmMainDbModels\WmSettingDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponSettingDao;
/**
 * 优惠券相关接口
 */
class Coupon extends open_controller
{
    /**
     * 获取该商家关注领优惠券设置
     */
    public function follow()
    {
        $rules = [
            ['field' => 'openid', 'label' => '用户openid', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '用户手机号', 'rules' => 'trim'],
            ['field' => 'visit_id', 'label' => 'visit_id', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        // 自动将aid转换为visit_id
        $aid = $this->convert_visit_id($fdata['visit_id']);

        $wmCouponDao = WmCouponDao::i($aid);
        // 检查该用户是否已经领取（openid）
        if (!empty($fdata['openid'])) {
            if ($wmCouponDao->getCount(['aid' => $aid, 'open_id' => $fdata['openid'], 'type' => 2]) > 0) {
                $this->json_do->set_error('006', '已领取过优惠券');
            }
        }

        // 检查该用户是否已经领取（mobile）
        if (!empty($fdata['mobile'])) {
            if ($wmCouponDao->getCount(['aid' => $aid, 'mobile' => $fdata['mobile'], 'type' => 2]) > 0) {
                $this->json_do->set_error('006', '已领取过优惠券');
            }
        }

        // 获取当前商家的活动设置
        $wmCouponSettingDao = WmCouponSettingDao::i($aid);
        $setting = $wmCouponSettingDao->getOneArray(['aid' => $aid, 'type' => 2]);

        // log_message('error', __METHOD__ . $aid);
        // log_message('error', __METHOD__ . json_encode($setting));

        // 活动已结束
        if (!$setting) {
            $this->json_do->set_error('004', '活动已结束');
        } else if ($setting['is_open'] != 1) {
            $this->json_do->set_error('004', '活动已结束');
        } else if (time() < $setting['start_time']) {
            $this->json_do->set_error('004', '活动未开始');
        } else if (time() > $setting['end_time']) {
            $this->json_do->set_error('004', '活动已结束');
        }

        // 查询自定义子域名
        $wmGzhConfigCache = new WmGzhConfigCache(['aid' => $aid]);
        $config = $wmGzhConfigCache->getDataASNX();

        //开启第三方授权登录
        if ($config->login_type == 1) {
            $scrm_sdk = new scrm_sdk('scrm_new');
            $result = $scrm_sdk->getGzhInfo(['visit_id' => $fdata['visit_id']]);
            if (!isset($result['data']['authorizer_appid'])) {
                if ($exit) {
                    $this->json_do->set_error('004', '开启第三方授权登录,未授权公众号');
                } else {
                    $sub_domain = '{null}';
                }

            } else {
                $sub_domain = $result['data']['authorizer_appid'];
            }

        } else {
            $sub_domain = $aid;
            $wmSettingDao = WmSettingDao::i($aid);
            $wm_setting = $wmSettingDao->getOne(['aid' => $aid]);

            if ($wm_setting && !empty($wm_setting->domain)) {
                $sub_domain = $wm_setting->domain;
            }
        }

        // 返回相关设置
        $ret = [
            'poster_pic' => 'https://img.alicdn.com/imgextra/i4/82637989/TB2TzoXjBDH8KJjy1zeXXXjepXa_!!82637989.png', // 展示图片
            'link_title' => '您好！您收到一张优惠券', // 图文标题
            'link_desc' => '您有一张优惠券没有领取，点击链接立即领券', // 图文描述
            'link_url' => 'https://' . $sub_domain . '.' . M_SUB_URL . '/#/follow-coupon/receive', // 链接地址
        ];

        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }
}
