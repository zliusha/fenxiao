<?php
/**
 * 微信储值相关接口（小程序用户端）
 * @author dadi
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
class Member extends xcx_user_controller
{
    // SCRM接口请求参数
    public $scrmParams = [];

    public function __construct()
    {
        parent::__construct();
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

    // 储值余额接口
    public function balance()
    {
        $api = ci_scrm::FANS_ACCOUNT;
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $this->json_do->set_data($result['data']);
        $this->json_do->out_put();
    }

    // 储值记录接口
    public function balance_history()
    {
        $rule = [
            ['field' => 'current_page', 'label' => '当前页', 'rules' => 'trim|numeric'],
            ['field' => 'page_size', 'label' => '单页个数', 'rules' => 'trim|numeric'],
            ['field' => 'type', 'label' => '类型', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $api = ci_scrm::FANS_PAY_LIST;
        $this->scrmParams['limit'] = $input['page_size'];
        $this->scrmParams['page'] = $input['current_page'];
        $this->scrmParams['type'] = $input['type'];
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $this->json_do->set_data($result['data']);
        $this->json_do->out_put();
    }

    // 积分余额接口
    public function integral()
    {
        $api = ci_scrm::INTEGRAL;
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $this->json_do->set_data($result['data']);
        $this->json_do->out_put();
    }

    // 积分记录接口
    public function integral_history()
    {
        $rule = [
            ['field' => 'current_page', 'label' => '当前页', 'rules' => 'trim|numeric'],
            ['field' => 'page_size', 'label' => '单页个数', 'rules' => 'trim|numeric'],
            ['field' => 'type', 'label' => '类型', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $api = ci_scrm::INTEGRAL_INFO;
        $this->scrmParams['limit'] = $input['page_size'];
        $this->scrmParams['page'] = $input['current_page'];
        $this->scrmParams['type'] = $input['type'];
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $this->json_do->set_data($result['data']);
        $this->json_do->out_put();
    }

    // 积分商城链接接口
    public function integral_mall_link()
    {
        $api = ci_scrm::GET_ACTIVITY_URL;
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $this->json_do->set_data($result['data']);
        $this->json_do->out_put();
    }

}
