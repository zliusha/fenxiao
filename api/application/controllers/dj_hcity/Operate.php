<?php

/**
 * 商圈后台经营数据接口
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/7/23
 * Time: 上午11:40
 */
use Service\Bll\Hcity\OperateBll;
use Service\Exceptions\Exception;
use Service\Support\FLock;

class Operate extends dj_hcity_controller
{

    /**
     * 提交提现接口
     * @author feiying<feiying@iyenei.com>
     */
    public function apply_money()
    {   
        if (FLock::getInstance()->lock(__METHOD__ . $this->s_user->user_id)) {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'integer|required'],
            ['field' => 'payment_account', 'label' => '收款账号', 'rules' => 'trim|required'],
            ['field' => 'payment_method', 'label' => '收款方式', 'rules' => 'trim|required'],
            ['field' => 'money', 'label' => '提现金额', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        $info = [
            'aid' => $this->s_user->aid,
            'visit_id' => $this->s_user->visit_id,
            'user_id' => $this->s_user->user_id
            ];
        try {
            $ret = (new OperateBll())->applyMoney($info, $data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '提现失败');
            }
         } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
        } else {
            $this->json_do->set_error('005', '提现过于频繁,请稍后再试。');
        }
    }
    
    /**
     * 提交提现银行卡数据接口
     * @author feiying<feiying@iyenei.com>
     */
    public function card_info()
    {   
       $rules = [
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        $ret = (new OperateBll())->cardInfo($data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 导出列表经营数据接口
     * @author feiying<feiying@iyenei.com>
     */
    public function redpacket_excel()
    {
        $rule = [
            ['field' => 'type', 'label' => '金额类型', 'rules' => 'integer'],
            ['field' => 'fid', 'label' => '订单号', 'rules' => 'integer'],
            ['field' => 'time', 'label' => '时间段查询', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $data['shop_id'] = $this->currentShopId;
        $ret = (new OperateBll())->applyMoneyExcel($this->s_user->aid,$data);
        $fields = [
          'fid' => '订单编号',
          'money' => '金额',
          'type' => '类型',
          'time' => '时间',
        ];
        $filename = 'balanceorder_excel_' . mt_rand(100, 999) . '_' . date('Y-m-d');
        $out['filename'] = $filename;
        $out['fields'] = $fields;
        $out['rows'] = $ret;
        $this->json_do->set_data($out);
        $this->json_do->out_put(); 
    }

    /**
     * 经营数据筛选列表
     * @author feiying<feiying@iyenei.com>
     */
    public function order_search()
    {
        $rule = [
            ['field' => 'type', 'label' => '金额类型', 'rules' => 'integer'],
            ['field' => 'fid', 'label' => '订单号', 'rules' => 'integer'],
            ['field' => 'time', 'label' => '时间段查询', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $data['shop_id'] = $this->currentShopId;
        $ret = (new OperateBll())->operateSearch($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

     /**
     * 经营首页经营数据
     * @author feiying<feiying@iyenei.com>
     */
    public function home_info()
    {
        $data['shop_id'] = $this->currentShopId;
        $data['time']= strtotime(date("Y-m-d"),time());
        $ret = (new OperateBll())->homeInfo($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }
}