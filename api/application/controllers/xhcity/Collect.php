<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/11
 * Time: 上午11:42
 */
use Service\Bll\Hcity\CollectBll;
use Service\Support\FLock;

class Collect extends xhcity_user_controller
{
    /**
     * 收藏商品
     * @author ahe<ahe@iyenei.com>
     */
    public function collect_goods()
    {
        if (!FLock::getInstance()->lock(__METHOD__ . $this->s_user->uid)) {
            $this->json_do->set_error('005', '操作过于频繁,请稍后再试。');
        }
        $rules = [
            ['field' => 'aid', 'label' => '公司id', 'rules' => 'integer|required'],
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'integer|required'],
            ['field' => 'goods_id', 'label' => '商品id', 'rules' => 'integer|required'],
            ['field' => 'source_type', 'label' => '来源类型', 'rules' => 'integer|required']
        ];

        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        if ($this->s_user->uid == 0) {
            $this->json_do->set_error('005', '收藏失败,请先登录');
        }
        try {
            $ret = (new CollectBll())->collectGoods($this->s_user->uid, $data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '收藏失败');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 取消收藏商品
     * @author ahe<ahe@iyenei.com>
     */
    public function cancel_collect_goods()
    {
        if (!FLock::getInstance()->lock(__METHOD__ . $this->s_user->uid)) {
            $this->json_do->set_error('005', '操作过于频繁,请稍后再试。');
        }
        $rules = [
            ['field' => 'aid', 'label' => '公司id', 'rules' => 'integer|required'],
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'integer|required'],
            ['field' => 'goods_id', 'label' => '商品id', 'rules' => 'integer|required'],
            ['field' => 'source_type', 'label' => '来源类型', 'rules' => 'integer|required']
        ];

        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        if ($this->s_user->uid == 0) {
            $this->json_do->set_error('005', '取消收藏失败,请先登录');
        }
        try {
            $ret = (new CollectBll())->cancelCollectGoods($this->s_user->uid, $data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '收藏失败');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 收藏店铺
     * @author ahe<ahe@iyenei.com>
     */
    public function collect_shop()
    {
        if (!FLock::getInstance()->lock(__METHOD__ . $this->s_user->uid)) {
            $this->json_do->set_error('005', '操作过于频繁,请稍后再试。');
        }
        $rules = [
            ['field' => 'aid', 'label' => '公司id', 'rules' => 'integer|required'],
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'integer|required'],
        ];
        /**
         * 收藏失败
         */
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        if ($this->s_user->uid == 0) {
            $this->json_do->set_error('005', '收藏失败,请先登录');
        }
        try {
            $ret = (new CollectBll())->collectShop($this->s_user->uid, $data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '收藏失败');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 取消收藏店铺
     * @author ahe<ahe@iyenei.com>
     */
    public function cancel_collect_shop()
    {
        if (!FLock::getInstance()->lock(__METHOD__ . $this->s_user->uid)) {
            $this->json_do->set_error('005', '操作过于频繁,请稍后再试。');
        }
        $rules = [
            ['field' => 'aid', 'label' => '公司id', 'rules' => 'integer|required'],
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'integer|required'],
        ];

        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        if ($this->s_user->uid == 0) {
            $this->json_do->set_error('005', '取消收藏失败,请先登录');
        }
        try {
            $ret = (new CollectBll())->cancelCollectShop($this->s_user->uid, $data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '收藏失败');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 获取收藏店铺列表
     * @author ahe<ahe@iyenei.com>
     */
    public function get_shop_list()
    {
        $rules = [
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        if ($this->s_user->uid == 0) {
            $this->json_do->set_error('005', '请先登录');
        }
        try {
            $list = (new CollectBll())->getCollectShopList($this->s_user->uid, $fdata);
            $this->json_do->set_data($list);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 获取收藏商品列表
     * @author ahe<ahe@iyenei.com>
     */
    public function get_goods_list()
    {
        $rules = [
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        if ($this->s_user->uid == 0) {
            $this->json_do->set_error('005', '请先登录');
        }
        try {
            $list = (new CollectBll())->getCollectGoodsList($this->s_user->uid, $fdata);
            $this->json_do->set_data($list);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }
}