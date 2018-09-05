<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/17
 * Time: 10:31
 * 红包信息
 */
use Service\Bll\Hcity\RedPacketBll;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityRedPacketConsumeDao;

class Packet extends xhcity_user_controller
{
    /**
     * 红包列表
     */
    public function get_list()
    {
        $rules = [
            ['field' => 'current_page', 'label' => '当前页数', 'rules' => 'numeric'],
            ['field' => 'page_size', 'label' => '分页大小', 'rules' => 'numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        if ($this->s_user->uid == 0) {
            $this->json_do->set_error('005', '获取失败,请先登录');
        }
        $input['order'] = 'a.time DESC';
        $input['where'] = 'a.money>0';
        $redPacketBll = new RedPacketBll();
        $data = $redPacketBll->userPacketList($this->s_user->uid, $input, true);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 红包详情+使用记录
     */
    public function detail()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'aid', 'label' => 'AID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        if ($this->s_user->uid == 0) {
            $this->json_do->set_error('005', '获取失败,请先登录');
        }
        $redPacketBll = new RedPacketBll();
        $data = $redPacketBll->detail($fdata['aid'], $this->s_user->uid, $fdata['shop_id']);

        //获取使用记录
        $data['use_list'] = ShcityRedPacketConsumeDao::i(['uid'=>$this->s_user->uid])->getAllArray(['uid'=>$this->s_user->uid, 'shop_id'=>$fdata['shop_id']], '*', "id DESC");
        $data['shop_info'] = MainShopDao::i()->getOne(['id'=>$fdata['shop_id']]);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}