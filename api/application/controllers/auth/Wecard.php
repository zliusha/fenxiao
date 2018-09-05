<?php
/**
 * 微信会员卡和优惠券（小程序用户端）
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmGzhConfigDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserWxbindDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
class Wecard extends xcx_controller
{
    // SCRM接口请求参数
    private $scrmParams = [];
    private $isNewScrm = false;

    public function __construct()
    {
        parent::__construct();

        $this->isNewScrm = is_new_scrm($this->aid);

        // 新版接口读取数据
        if ($this->isNewScrm) {
            if (in_array(ENVIRONMENT, ['development'])) {
                $this->scrmParams['openid'] = 'ovPui0Vs-iKyricaa40m-FY84WBs';
                $this->scrmParams['visit_id'] = '9169744';
                $this->scrmParams['phone'] = '15505885184';
            } else {
                // 获取openid
                $this->scrmParams['openid'] = $this->s_user->ext['open_id'];
                // 获取visit_id
                $mainCompanyDao = MainCompanyDao::i();
                $m_main_company = $mainCompanyDao->getOne(['id' => $this->aid], 'visit_id');

                $this->scrmParams['visit_id'] = $m_main_company->visit_id;
                // 获取手机号
                $this->scrmParams['phone'] = $this->s_user->mobile;
            }
        }
    }

    // 微信会员卡信息
    public function member_card()
    {
        // 兼容老版本接口
        if (!$this->isNewScrm) {
            $this->_member_card();
            exit;
        }

        // 获取卡列表
        $api = ci_scrm::GET_SPECIFIC_FAN_COUPON;
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }

        // 暂无数据
        if ($result['code'] == 1000) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        foreach ($result['data'] as $row) {
            if ($row['cardInfo']['card_type'] == 'MEMBER_CARD') {
                $card = $row;
                break;
            }
        }

        if (!$card) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        // 获取卡信息详情
        $api = ci_scrm::GET_CARD_INFO;
        $this->scrmParams['code'] = $card['code'];
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', '读取失败，请稍候重试');
        }

        $this->json_do->set_data($result['data']);
        $this->json_do->out_put();
    }

    // 微信会员卡领卡链接
    public function member_card_link()
    {
        // 兼容老版本接口
        if (!$this->isNewScrm) {
            $this->_member_card_link();
            exit;
        }

        $api = ci_scrm::GET_MEMBER_LINK;
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        // 暂无数据
        if ($result['code'] == 1000) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $this->json_do->set_data(['link' => str_replace('http://', 'https://', $result['data'])]);
        $this->json_do->out_put();
    }

    // 微信会员卡领卡信息
    public function member_card_info()
    {
        // 兼容老版本接口
        if (!$this->isNewScrm) {
            $this->_member_card_info();
            exit;
        }

        $api = ci_scrm::GET_MEMBER_CARD_INFO;
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        // 暂无数据
        if ($result['code'] == 1000) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $this->json_do->set_data($result['data']);
        $this->json_do->out_put();
    }

    // 微信优惠券列表
    public function coupon_card()
    {
        // 兼容老版本接口
        if (!$this->isNewScrm) {
            $this->_coupon_card();
            exit;
        }

        $rule = [
            ['field' => 'type', 'label' => '类型', 'rules' => 'trim|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $fdata['type'] = $fdata['type'] ? $fdata['type'] : 0;

        $api = ci_scrm::GET_SPECIFIC_FAN_COUPON;
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        // 暂无数据
        if ($result['code'] == 1000) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', '读取失败，请稍候重试');
        }

        $list = [];

        if (is_array($result['data'])) {
            foreach ($result['data'] as $row) {
                if ($row['cardInfo']['card_type'] == 'CASH') {
                    if ($fdata['type'] == 1) {
                        if (time() >= $row['cardInfo']['cash']['base_info']['date_info']['begin_timestamp'] && time() <= $row['cardInfo']['cash']['base_info']['date_info']['end_timestamp']) {
                            $list[] = $row;
                        }
                    } else {
                        $list[] = $row;
                    }
                }
            }
        }

        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    // ============ 微信小程序领取会员卡 ============
    // 添加微信卡券签名获取
    public function member_card_signature()
    {
        $cardId = $this->input->post_get('card_id');

        $access_token = $this->getAccessToken();
        $ticket = $this->getApiTicket($access_token);
        $time = time();
        $nonce_str = $this->createNonceStr();
        $arr = [
            $ticket,
            $time,
            $cardId,
            $nonce_str,
        ];
        sort($arr, SORT_STRING);
        $dataStr = implode('', $arr);
        $signature = sha1($dataStr);
        $cardExt = [
            'timestamp' => $time,
            'signature' => $signature,
            'nonce_str' => $nonce_str,
        ];

        $this->json_do->set_data($cardExt);
        $this->json_do->out_put();
    }

    private function getAccessToken()
    {
        $wmGzhConfigDao = WmGzhConfigDao::i($this->aid);
        $m_wx_config = $wmGzhConfigDao->getOne(['aid' => $this->aid]);

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$m_wx_config->app_id&secret=$m_wx_config->app_secret";
        $res = json_decode(http_get($url));
        if (empty($res) || !isset($res->access_token)) {
            log_message('error', "jssdk--token--[appID:{$m_wx_config->app_id}]--" . json_encode($res));
            return false;
        }

        $access_token = $res->access_token;

        return $access_token;
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getApiTicket($access_token)
    {
        $res = http_get("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=wx_card");

        $res = json_decode($res, true);

        if (empty($res) || !isset($res['ticket'])) {
            log_message('error', "apiticket--[aid:{$this->aid}]--" . json_encode($res));
            return false;
        }

        $ticket = $res['ticket'];

        return $res['ticket'];
    }
    // ============ 微信小程序领取会员卡 ============

    // ============ 待数据迁移完成后以下代码可以删除 ============
    // 微信会员卡信息（兼容老版本接口）
    private function _member_card()
    {
        if (in_array(ENVIRONMENT, ['development'])) {
            $openId = 'ovPui0Vs-iKyricaa40m-FY84WBs';
            $visitId = '9169744';
        } else {
            // 获取openid
            $wmUserWxbindDao = WmUserWxbindDao::i($this->aid);
            $wxbind = $wmUserWxbindDao->getOne(['uid' => $this->s_user->uid, 'type' => 'weixin', 'aid' =>$this->aid], 'open_id', 'id desc');
            $openId = $wxbind->open_id;
            // 获取visit_id
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne(['id' => $this->aid], 'visit_id');
            $visitId = $m_main_company->visit_id;
        }

        // openId未绑定
        if (!$openId) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        // 获取卡列表
        $api = ci_wxcoupon::GET_SPECIFIC_FAN_COUPON;
        $params = ['openId' => $openId, 'visitId' => $visitId];
        if (!$visitId) {
            log_message('error', '小程序visitId异常:' . json_encode($params) . ' s_user:' . json_encode($this->s_user) . ' aid:' . $this->aid);
        }
        $result = ci_wxcoupon::call($api, $params);

        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        foreach ($result['data'] as $row) {
            if ($row['cardInfo']['card_type'] == 'MEMBER_CARD') {
                $card = $row;
                break;
            }
        }

        if (!$card) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        // 获取卡信息详情
        $api = ci_wxcoupon::GET_CARD_INFO;
        $params = ['code' => $card['code'], 'visitId' => $visitId];
        $result = ci_wxcoupon::call($api, $params);

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', '读取失败，请稍候重试');
        }

        $this->json_do->set_data($result['data']);
        $this->json_do->out_put();
    }

    // 微信会员卡领卡链接（兼容老版本接口）
    private function _member_card_link()
    {
        $api = ci_wxcoupon::GET_MEMBER_LINK;
        if (in_array(ENVIRONMENT, ['development'])) {
            $params = ['visitId' => '9169744'];
        } else {
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne(['id' => $this->aid], 'visit_id');

            $params = ['visitId' => $m_main_company->visit_id];
        }
        $result = ci_wxcoupon::call($api, $params);

        // 暂无数据
        if ($result['code'] == 1000) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $this->json_do->set_data(['link' => str_replace('http://', 'https://', $result['data'])]);
        $this->json_do->out_put();
    }

    // 微信会员卡领卡信息（兼容老版本接口）
    private function _member_card_info()
    {
        $api = ci_wxcoupon::GET_MEMBER_CARD_INFO;
        if (in_array(ENVIRONMENT, ['development'])) {
            $params = ['visitId' => '9169744'];
        } else {
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne(['id' => $this->aid], 'visit_id');

            $params = ['visitId' => $m_main_company->visit_id];
        }
        $result = ci_wxcoupon::call($api, $params);

        // 暂无数据
        if ($result['code'] == 1000) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $this->json_do->set_data($result['data']);
        $this->json_do->out_put();
    }

    // 微信优惠券列表（兼容老版本接口）
    private function _coupon_card()
    {
        if (in_array(ENVIRONMENT, ['development'])) {
            $openId = 'ovPui0Vs-iKyricaa40m-FY84WBs';
            $visitId = '9169744';
        } else {
            // 获取openid
            $wmUserWxbindDao = WmUserWxbindDao::i($this->aid);
            $wxbind = $wmUserWxbindDao->getOne(['uid' => $this->s_user->uid, 'type' => 'weixin', 'aid' =>$this->aid], 'open_id', 'id desc');

            $openId = $wxbind->open_id;
            // 获取visit_id
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne(['id' => $this->aid], 'visit_id');

            $visitId = $m_main_company->visit_id;
        }

        // openId未绑定
        if (!$openId) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        $api = ci_wxcoupon::GET_SPECIFIC_FAN_COUPON;
        $params = ['openId' => $openId, 'visitId' => $visitId];
        $result = ci_wxcoupon::call($api, $params);

        // 暂无数据
        if ($result['code'] == 1000) {
            $this->json_do->set_data([]);
            $this->json_do->out_put();
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', '读取失败，请稍候重试');
        }

        $list = [];

        foreach ($result['data'] as $row) {
            if ($row['cardInfo']['card_type'] == 'CASH') {
                $list[] = $row;
            }
        }

        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }
    // ============ 待数据迁移完成后以上代码可以删除 ============

}
