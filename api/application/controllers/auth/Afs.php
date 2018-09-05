<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 售后处理接口（小程序用户端）
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
class Afs extends xcx_user_controller
{

    /**
     * 查询订单可退详情
     */
    public function availableRefund()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|numeric|required'],
        ];

        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $wmOrderDao = WmOrderDao::i($this->aid);
        $result = $wmOrderDao->getOneArray(['uid' => $this->s_user->uid, 'tid' => $input['tradeno'], 'aid'=>$this->aid], 'status,pay_money,pay_time,afsno');
        $ret['order_pay_money'] = $result['pay_money'];

        if (in_array($result['status'], ['2020', '2030', '2035', '2040', '2050', '2060', '6060']) && time() < order_refund_expire($result['pay_time']) && empty($result['afsno'])) {
            if (in_array($result['status'], ['2020', '2030', '2040'])) {
                $ret['available_type'] = '1';
            } else {
                $ret['available_type'] = '1,2';
            }
        } else {
            $this->json_do->set_error('004', '该订单不支持退款申请');
        }

        $wmOrderExtDao = WmOrderExtDao::i($this->aid);
        $result = $wmOrderExtDao->getAllArray(['uid' => $this->s_user->uid, 'tid' => $input['tradeno'], 'aid'=>$this->aid], 'ext_tid,goods_title,num,pay_money');
        foreach ($result as $key => $row) {
            $result[$key]['unit_price'] = bcdiv($row['pay_money'], $row['num'], 2);
        }
        $ret['order_ext'] = $result;

        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 创建售后请求
     */
    public function create()
    {
        $rule = [
            ['field' => 'type', 'label' => '售后类型', 'rules' => 'trim|in_list[1,2]|required'],
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|numeric|required'],
            ['field' => 'reason', 'label' => '退款原因', 'rules' => 'trim|required'],
            ['field' => 'remark', 'label' => '备注', 'rules' => 'trim'],
            ['field' => 'afs_detail', 'label' => '售后申请详情', 'rules' => 'trim'],
        ];

        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $input['uid'] = $this->s_user->uid;
        $input['aid'] = $this->aid;
        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->orderApplyRefund($input);
    }

    /**
     * 撤销售后请求
     */
    public function cancel()
    {
        $rule = [
            ['field' => 'afsno', 'label' => '售后单编号', 'rules' => 'trim|numeric|required'],
        ];

        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $input['uid'] = $this->s_user->uid;
        $input['aid'] = $this->aid;
        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->afsBuyerCancel($input);
    }

}
