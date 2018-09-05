<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/9/3
 * Time: 下午2:53
 */
use Service\Bll\Hcity\ActivityBargainBll;
use Service\Support\FLock;

class Bargain extends xhcity_user_controller
{
    function __construct()
    {
        parent::__construct(['ydym_goods_list', 'hcity_goods_list', 'goods_detail', 'has_activity', 'other_activity_goods']);
    }

    /**
     * 一店一码砍价商品列表
     * @author ahe<ahe@iyenei.com>
     */
    public function ydym_goods_list()
    {
        $rules = [
            ['field' => 'aid', 'label' => '商户id', 'rules' => 'trim|required|numeric'],
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|required|numeric'],
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $fdata['uid'] = $this->s_user->uid;
        $list = (new ActivityBargainBll())->getYdymGoodsList($fdata);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 商圈砍价商品列表
     * @author ahe<ahe@iyenei.com>
     */
    public function hcity_goods_list()
    {
        $rules = [
            ['field' => 'city_code', 'label' => '区域码', 'rules' => 'trim'],
            ['field' => 'aid', 'label' => '公司id', 'rules' => 'trim|numeric'],
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|numeric'],
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $fdata['uid'] = $this->s_user->uid;
        if (empty($fdata['city_code']) && (empty($fdata['aid']) || empty($fdata['shop_id']))) {
            $this->json_do->set_error('005', '参数错误');
        }
        $list = (new ActivityBargainBll())->getHcityGoodsList($fdata);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 砍价商品详情
     * @author ahe<ahe@iyenei.com>
     */
    public function goods_detail()
    {
        $rules = [
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $fdata['uid'] = $this->s_user->uid;
        try {
            $detail = (new ActivityBargainBll())->getActivityDetail($fdata);
            $this->json_do->set_data($detail);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 砍价
     * @author ahe<ahe@iyenei.com>
     */
    public function do_bargain()
    {
        $rules = [
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $fdata['uid'] = $this->s_user->uid;
        $fdata['username'] = $this->s_user->nickname;
        $fdata['mobile'] = $this->s_user->mobile;
        if (!FLock::getInstance()->lock('Bargain:do_bargain:' . $fdata['uid'])) { // TODO 键名与活动下单同用，请注意
            $this->json_do->set_error('005', '系统繁忙，请重试');
        }
        try {
            $bargainPrice = (new ActivityBargainBll())->doBargain($fdata);
            $this->json_do->set_data(['bargain_price' => $bargainPrice]);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 判断店铺是否有砍价活动
     * @author ahe<ahe@iyenei.com>
     */
    public function has_activity()
    {
        $rules = [
            ['field' => 'city_code', 'label' => '区域码', 'rules' => 'trim'],
            ['field' => 'aid', 'label' => '公司id', 'rules' => 'trim|numeric'],
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|numeric'],
            ['field' => 'source_type', 'label' => '来源', 'rules' => 'trim|numeric'],//1商圈 2一店一码
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        if (empty($fdata['city_code']) && (empty($fdata['aid']) || empty($fdata['shop_id']) || empty($fdata['source_type']))) {
            $this->json_do->set_error('005', '参数错误');
        }
        $ret = (new ActivityBargainBll())->hasActivity($fdata);
        $this->json_do->set_data(['result' => $ret]);
        $this->json_do->out_put();
    }

    /**
     * 获取更多优惠商品
     * @author ahe<ahe@iyenei.com>
     */
    public function other_activity_goods()
    {
        $rules = [
            ['field' => 'aid', 'label' => '公司id', 'rules' => 'trim|required|numeric'],
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|required|numeric'],
            ['field' => 'goods_id', 'label' => '商品id', 'rules' => 'trim|required|numeric'],
            ['field' => 'source_type', 'label' => '来源', 'rules' => 'trim|required|numeric'],//1商圈 2一店一码
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $info = (new ActivityBargainBll())->getOtherActivityGoods($fdata);
        $this->json_do->set_data($info);
        $this->json_do->out_put();
    }
}