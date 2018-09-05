<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 促销活动-优惠券管理接口
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponSettingDao;
class Coupon_api extends wm_service_controller
{
    /**
     * 加载优惠券设置
     */
    public function load($type = 1)
    {
        $wmCouponSettingDao = WmCouponSettingDao::i($this->s_user->aid);
        $coupon_setting = $wmCouponSettingDao->getOneArray(['aid' => $this->s_user->aid, 'type' => $type]);

        if ($coupon_setting) {
            $coupon_setting['start_time'] = date('Y-m-d H:i:s', $coupon_setting['start_time']);
            $coupon_setting['end_time'] = date('Y-m-d H:i:s', $coupon_setting['end_time']);
            $coupon_setting['use_start_time'] = date('Y-m-d H:i:s', $coupon_setting['use_start_time']);
            $coupon_setting['use_end_time'] = date('Y-m-d H:i:s', $coupon_setting['use_end_time']);
        }

        $this->json_do->set_data($coupon_setting);
        $this->json_do->out_put();
    }

    /**
     * 保存优惠券设置
     */
    public function save()
    {
        $rule = [
            ['field' => 'title', 'label' => '优惠券标题', 'rules' => 'trim|required'],
            ['field' => 'amount_type', 'label' => '面额类型', 'rules' => 'trim|required|numeric'],
            ['field' => 'amount', 'label' => '固定面额', 'rules' => 'trim|required|numeric'],
            ['field' => 'amount_region', 'label' => '面额区间', 'rules' => 'trim|required'],
            ['field' => 'quantity', 'label' => '优惠券数量', 'rules' => 'trim|required|numeric'],
            ['field' => 'start_time', 'label' => '活动开始时间', 'rules' => 'trim|strtotime|required'],
            ['field' => 'end_time', 'label' => '活动结束时间', 'rules' => 'trim|strtotime|required'],
            ['field' => 'use_start_time', 'label' => '使用开始时间', 'rules' => 'trim|strtotime|required'],
            ['field' => 'use_end_time', 'label' => '使用结束时间', 'rules' => 'trim|strtotime|required'],
            ['field' => 'type', 'label' => '优惠券类型', 'rules' => 'trim|required|numeric|in_list[1,2]'],
            ['field' => 'condition_limit', 'label' => '限制条件', 'rules' => 'trim|numeric|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmCouponSettingDao = WmCouponSettingDao::i($this->s_user->aid);

        // // 促销类型 1裂变优惠券 2关注领优惠券（默认数量为1）
        // $fdata['quantity'] = $fdata['type'] == 2 ? 1 : $fdata['quantity'];

        // 检查数据是否存在
        $count = $wmCouponSettingDao->getCount(['aid' => $this->s_user->aid, 'type' => $fdata['type']]);

        if ($count >= 1) {
            // 更新记录
            $fdata['update_time'] = time();
            $result = $wmCouponSettingDao->update($fdata, ['aid' => $this->s_user->aid, 'type' => $fdata['type']]);
        } else {
            // 创建记录
            $fdata['aid'] = $this->s_user->aid;
            $fdata['update_time'] = 0;
            $fdata['status'] = 1;
            $result = $wmCouponSettingDao->create($fdata);
        }

        if ($result !== false) {
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '保存失败');
        }
    }

    /**
     * 结束优惠券活动
     */
    public function close()
    {
        $rule = [
            ['field' => 'type', 'label' => '优惠券类型', 'rules' => 'trim|numeric|required|in_list[1,2]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmCouponSettingDao = WmCouponSettingDao::i($this->s_user->aid);
        $result = $wmCouponSettingDao->update(['status' => 3], ['aid' => $this->s_user->aid, 'type' => $fdata['type']]);

        if ($result !== false) {
            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '操作失败');
        }
    }

    /**
     * 删除优惠券设置
     */
    public function delete()
    {
        $rule = [
            ['field' => 'type', 'label' => '优惠券类型', 'rules' => 'trim|numeric|required|in_list[1,2]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmCouponSettingDao = WmCouponSettingDao::i($this->s_user->aid);
        $result = $wmCouponSettingDao->delete(['aid' => $this->s_user->aid, 'type' => $fdata['type']]);

        if ($result !== false) {
            $this->json_do->set_msg('删除成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '删除失败');
        }
    }

    /**
     * 优惠券设置开关
     */
    public function onoff()
    {
        $rule = [
            ['field' => 'type', 'label' => '优惠券类型', 'rules' => 'trim|numeric|required|in_list[1,2]'],
            ['field' => 'value', 'label' => '开关值', 'rules' => 'trim|numeric|required|in_list[0,1]'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmCouponSettingDao = WmCouponSettingDao::i($this->s_user->aid);
        $result = $wmCouponSettingDao->update(['is_open' => $fdata['value']], ['aid' => $this->s_user->aid, 'type' => $fdata['type']]);

        if ($result !== false) {
            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '操作失败');
        }
    }

    /**
     * 用户优惠券领取记录
     */
    public function record($type = 1)
    {
        $rule = [
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim'],
            ['field' => 'status', 'label' => '状态', 'rules' => 'trim|numeric|in_list[1,2,3]'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->fields = '*';
        $p_conf->table = "{$wmShardDb->tables['wm_coupon']}";
        $p_conf->where = " aid = {$this->s_user->aid} AND type = {$type} ";
        if($fdata['mobile']) {
            $p_conf->where .= " AND mobile = '{$fdata['mobile']}' ";
        }
        if($fdata['status']) {
            $p_conf->where .= " AND `status` = '{$fdata['status']}' ";
        }
        $p_conf->order = 'id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        $v_rules = array(
            array('type' => 'time', 'field' => 'time', 'format' => 'Y-m-d H:i:s'),
        );
        $list = convert_client_list($list, $v_rules);

        $data['total'] = $count;
        $data['rows'] = $list;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

}
