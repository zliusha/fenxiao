<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/8/22
 * Time: 上午11:20
 */
use Service\Bll\Hcity\ActivityJzBll;
use Service\Support\FLock;

class Activity extends xhcity_user_controller
{
    function __construct()
    {
        parent::__construct(['ydym_jz_goods_list', 'hcity_jz_goods_list', 'jz_goods_detail', 'jz_helper_list', 'has_jz_activity', 'other_activity_goods']);
    }

    /**
     * 一店一码集赞商品列表
     * @author ahe<ahe@iyenei.com>
     */
    public function ydym_jz_goods_list()
    {
        $rules = [
            ['field' => 'aid', 'label' => '商户id', 'rules' => 'trim|required|numeric'],
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|required|numeric'],
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $list = (new ActivityJzBll())->getYdymJzGoodsList($fdata);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 商圈集赞商品列表
     * @author ahe<ahe@iyenei.com>
     */
    public function hcity_jz_goods_list()
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
        if (empty($fdata['city_code']) && (empty($fdata['aid']) || empty($fdata['shop_id']))) {
            $this->json_do->set_error('005', '参数错误');
        }
        $list = (new ActivityJzBll())->getHcityJzGoodsList($fdata);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 点赞商品详情
     * @author ahe<ahe@iyenei.com>
     */
    public function jz_goods_detail()
    {
        $rules = [
//            ['field' => 'aid', 'label' => '商户id', 'rules' => 'trim|required|numeric'],
//            ['field' => 'goods_id', 'label' => '商品id', 'rules' => 'trim|required|numeric'],
//            ['field' => 'source_type', 'label' => '来源', 'rules' => 'trim|required|numeric'],//1商圈 2一店一码
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $fdata['uid'] = $this->s_user->uid;
        try {
            $detail = (new ActivityJzBll())->getJzGoodsDetail($fdata);
            $this->json_do->set_data($detail);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 参与集赞
     * @author ahe<ahe@iyenei.com>
     */
    public function join_jz()
    {
        $rules = [
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        try {
            $fdata['uid'] = $this->s_user->uid;
            $fdata['username'] = $this->s_user->nickname;
            $fdata['mobile'] = $this->s_user->mobile;
            $ret = (new ActivityJzBll())->joinJzActivity($fdata);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '参与失败');
            }
        } catch (Throwable $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 助力集赞
     * @author ahe<ahe@iyenei.com>
     */
    public function help_jz()
    {
        $rules = [
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'trim|required|numeric'],
            ['field' => 'join_uid', 'label' => '活动发起者uid', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        if (!FLock::getInstance()->lock(__METHOD__ . $fdata['join_uid'])) {
            $this->json_do->set_error('005', '系统繁忙，请重试');
        }
        try {
            $fdata['helper_uid'] = $this->s_user->uid;
            $fdata['helper_name'] = $this->s_user->nickname;
            $fdata['helper_img'] = $this->s_user->headimg;
            $ret = (new ActivityJzBll())->helpJzActivity($fdata);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '参与失败');
            }
        } catch (Throwable $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 集赞助力榜
     * @author ahe<ahe@iyenei.com>
     */
    public function jz_helper_list()
    {
        $rules = [
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'trim|required|numeric'],
            ['field' => 'join_uid', 'label' => '活动发起者uid', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $list = (new ActivityJzBll())->getJzHelperList($fdata);
        $this->json_do->set_data($list);
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
        $info = (new ActivityJzBll())->getOtherActivityGoods($fdata);
        $this->json_do->set_data($info);
        $this->json_do->out_put();
    }

    /**
     * 判断店铺是否有集赞活动
     * @author ahe<ahe@iyenei.com>
     */
    public function has_jz_activity()
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
        $ret = (new ActivityJzBll())->hasJsActivity($fdata);
        $this->json_do->set_data(['result' => $ret]);
        $this->json_do->out_put();
    }
}