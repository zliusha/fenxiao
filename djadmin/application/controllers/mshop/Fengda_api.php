<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/6/25
 * Time: 9:25
 */
class Fengda_api extends wm_service_controller
{
    /**
     * 商户资金记录
     */
    public function money_list()
    {
        $current_page = (int)$this->input->post_get('current_page');
        $page_size = (int)$this->input->post_get('page_size');
        try{
            $ci_fengda = new ci_fengda();

            $params['merchantId'] = $this->s_user->aid;
            $params['currentNum'] = $current_page;
            $params['pageSize'] = $page_size;
            $result = $ci_fengda->request($params, ci_fengda::MERCHANT_RECHARGE_LIST);
            $data['rows'] = $result['data'];
            $data['total'] = $result['other'];
            $data['money'] = $result['ext'];

            $this->json_do->set_data($data);
            $this->json_do->out_put();

        }catch(Exception $e) {
            $errMsg = $e->getMessage();
            log_message('error', __METHOD__ . '--' . "风达获取商户余额明细失败-" . $errMsg);
            $this->json_do->set_error('005',$errMsg);
        }
    }

    /**
     * 风达商户充值（充值到风达代理商）
     */
    public function recharge()
    {
        $rules=[
            ['field'=>'type','label'=>'支付类型','rules'=>'trim|required|in_list[1,2]'],
            ['field'=>'amount','label'=>'充值金额','rules'=>'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        if($fdata['amount'] <= 0)
        {
            $this->json_do->set_error('005', '充值金额需大于0');
        }

        try{
            $ci_fengda = new ci_fengda();

            $params['merchantId'] = $this->s_user->aid;
            $params['type'] = $fdata['type'];
            $params['amount'] = $fdata['amount'];
            $params['time'] = time();
            $result = $ci_fengda->request($params, ci_fengda::MERCHANT_RECHARGE);
            $recharge['pay_link'] = $result['data'];
            $recharge['type'] = $fdata['type'];
            $recharge['amount'] = $fdata['amount'];

            $data['info'] = $recharge;
            $this->json_do->set_data($data);
            $this->json_do->out_put();

        }catch(Exception $e) {
            $errMsg = $e->getMessage();
            log_message('error', __METHOD__ . '--' . "风达获取充值链接失败-" . $errMsg);
            $this->json_do->set_error('005',$errMsg);
        }

    }
}