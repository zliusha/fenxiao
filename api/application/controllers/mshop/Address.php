<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 地址簿接口
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmReceiverAddressDao;
class Address extends mshop_user_controller
{
    /**
     * 地址列表查询
     */
    public function index()
    {
        $wmReceiverAddressDao = WmReceiverAddressDao::i($this->aid);
        $list = $wmReceiverAddressDao->getAllArray(['uid' => $this->s_user->uid, 'aid'=>$this->aid]);

        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 获取收货地址详情
     */
    public function info()
    {
        $rule = [
            ['field' => 'receiver_address_id', 'label' => '地址ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $receiver_address_id = $f_data['receiver_address_id'];

        $wmReceiverAddressDao = WmReceiverAddressDao::i($this->aid);
        $model = $wmReceiverAddressDao->getOne(['id' => $receiver_address_id, 'aid'=>$this->aid]);

        $this->json_do->set_data($model);
        $this->json_do->out_put();
    }

    /**
     * 新增地址
     */
    public function create()
    {

        $rule = [
            ['field' => 'receiver_name', 'label' => '收货人姓名', 'rules' => 'trim|required'],
            ['field' => 'receiver_phone', 'label' => '收货人电话', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'receiver_site', 'label' => '收货人地址', 'rules' => 'trim|required'],
            ['field' => 'receiver_address', 'label' => '门牌号', 'rules' => 'trim'],
            ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
            ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required'],
            ['field' => 'tag', 'label' => '标签', 'rules' => 'trim'],
            ['field' => 'sex', 'label' => '性别', 'rules' => 'trim'],
            // ['field' => 'is_default', 'label' => '默认方式', 'rules' => 'trim']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $f_data['uid'] = $this->s_user->uid;
        $f_data['aid'] = $this->aid;
        $wmReceiverAddressDao = WmReceiverAddressDao::i($this->aid);
        $receiver_address_id = $wmReceiverAddressDao->create($f_data);

        if ($receiver_address_id > 0) {
            $this->json_do->set_msg('添加成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005', '添加失败');

    }

    /**
     * 收货地址编辑
     */
    public function edit()
    {

        $rule = [
            ['field' => 'receiver_name', 'label' => '收货人姓名', 'rules' => 'trim|required'],
            ['field' => 'receiver_phone', 'label' => '收货人电话', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'receiver_site', 'label' => '收货人地址', 'rules' => 'trim|required'],
            ['field' => 'receiver_address', 'label' => '门牌号', 'rules' => 'trim'],
            ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
            ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required'],
            ['field' => 'sex', 'label' => '性别', 'rules' => 'trim'],
            ['field' => 'tag', 'label' => '标签', 'rules' => 'trim'],
            // ['field' => 'is_default', 'label' => '默认方式', 'rules' => 'trim'],
            ['field' => 'receiver_address_id', 'label' => 'ID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $receiver_address_id = $f_data['receiver_address_id'];
        unset($f_data['receiver_address_id']);

        $f_data['aid'] = $this->aid;
        $wmReceiverAddressDao = WmReceiverAddressDao::i($this->aid);

        if ($wmReceiverAddressDao->update($f_data, ['id' => $receiver_address_id, 'aid'=>$this->aid]) !== false) {
            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        }

        $this->json_do->set_error('005', '修改失败');

    }
    /**
     * 设置默认收货地址
     */
    public function set_default()
    {
        $rule = [
            ['field' => 'receiver_address_id', 'label' => '地址ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'is_default', 'label' => '默认类型', 'rules' => 'trim|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $receiver_address_id = $f_data['receiver_address_id'];

        $wmReceiverAddressDao = WmReceiverAddressDao::i($this->aid);
        $wmReceiverAddressDao->update(['is_default' => 0], ['uid' => $this->s_user->uid, 'aid'=>$this->aid]);
        $wmReceiverAddressDao->update(['is_default' => 1], ['id' => $receiver_address_id, 'uid' => $this->s_user->uid, 'aid'=>$this->aid]);

        $this->json_do->set_msg('设置成功');
        $this->json_do->out_put();

    }

    /**
     * 收货地址删除
     */
    public function del()
    {
        $rule = [
            ['field' => 'receiver_address_id', 'label' => '地址ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $receiver_address_id = $f_data['receiver_address_id'];

        $wmReceiverAddressDao = WmReceiverAddressDao::i($this->aid);
        $wmReceiverAddressDao->delete(['id' => $receiver_address_id, 'uid' => $this->s_user->uid, 'aid'=>$this->aid]);

        $this->json_do->set_msg('删除成功');
        $this->json_do->out_put();

    }
}
