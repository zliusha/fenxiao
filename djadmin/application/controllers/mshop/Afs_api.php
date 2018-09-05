<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 售后处理接口（After-sales service）
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmAfsDao;
class Afs_api extends wm_service_controller
{

    // 枚举数据
    private $enum = [];

    /**
     * 查询售后详情
     */
    public function detail()
    {
        $id = (int) $this->input->post_get('id');

        $wmAfsDao = WmAfsDao::i($this->s_user->aid);
        $row = $wmAfsDao->getOneArray(['afsno' => $id, 'aid' => $this->s_user->aid]);

        $row['time'] = date('Y-m-d H:i', $row['time']);
        $row['update_time'] = date('Y-m-d H:i', $row['update_time']);

        $this->enum = &inc_config('waimai');

        $row['status'] = $this->enum('afs_status', $row['status']);

        $this->json_do->set_data($row);
        $this->json_do->out_put();
    }

    // 枚举标准格式化
    public function enum($key_name = '', $code = '', $unkown = '')
    {
        if (!isset($this->enum[$key_name])) {
            return [
                'type' => 'enum',
                'value' => 0,
                'alias' => '未知枚举类型',
            ];
        }
        return [
            'type' => 'enum',
            'value' => $code,
            'alias' => isset($this->enum[$key_name][$code]) ? $this->enum[$key_name][$code] : $unkown,
        ];
    }

    /**
     * 同意售后申请
     */
    public function agree()
    {
        $rule = [
            ['field' => 'afsno', 'label' => '售后单编号', 'rules' => 'trim|numeric|required'],
        ];
        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $input['aid'] = $this->s_user->aid;

        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->afsSellerAgree($input);
    }

    /**
     * 拒绝售后申请
     */
    public function refuse()
    {
        $rule = [
            ['field' => 'afsno', 'label' => '售后单编号', 'rules' => 'trim|numeric|required'],
            ['field' => 'reason', 'label' => '拒绝原因', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $input['aid'] = $this->s_user->aid;

        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->afsSellerRefuse($input);
    }

}
