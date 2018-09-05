<?php

/**
 * 一店一码后台商品接口
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/7/10
 * Time: 上午11:40
 */
use Service\Bll\Hcity\GoodsBll;
use Service\Exceptions\Exception;

class Goods extends dj_ydym_controller
{

    /**
     * 提交商品
     * @author feiying<feiying@iyenei.com>
     */
    public function create()
    {
        //验证店铺有效性
        $this->validYdymShop();

        $rules = [
            ['field' => 'title', 'label' => '产品标题', 'rules' => 'trim|required'],
            ['field' => 'pic_url', 'label' => '商品主图', 'rules' => 'trim|required'],
            ['field' => 'pic_url_list', 'label' => '商品图集', 'rules' => 'trim'],
            ['field' => 'desc', 'label' => '产品描述', 'rules' => 'trim|required',"xss_clean"=>false],
            ['field' => 'limit_num', 'label' => '每日限购数量', 'rules' => 'integer|required'],
            ['field' => 'sku_attr', 'label' => '多规格商品描述信息', 'rules' => 'required'],
            ['field' => 'use_end_time', 'label' => '用户使用的到期时间', 'rules' => 'required'],
            ['field' => 'goods_limit', 'label' => '商品每日限购', 'rules' => 'integer|required'],
            ['field' => 'total_limit_num', 'label' => '用户总限购', 'rules' => 'integer|required'],
            ['field' => 'is_limit_open', 'label' => '限购规则', 'rules' => 'integer|required'],
        ];

        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        $data['shop_id'] = $this->currentShopId;
        $ret = (new GoodsBll())->createGoods($this->s_user->aid, $data);
        if ($ret) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('001', '新增商品错误');
        }
    }

    /**
     * 编辑商品
     * @author feiying<feiying@iyenei.com>
     */
    public function edit()
    {   
        //验证店铺有效性
        $this->validYdymShop();
        $rule = [
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'integer|required'],
            ['field' => 'id', 'label' => '商品id', 'rules' => 'integer|required'],
            ['field' => 'title', 'label' => '产品标题', 'rules' => 'trim|required'],
            ['field' => 'pic_url', 'label' => '商品主图', 'rules' => 'trim|required'],
            ['field' => 'pic_url_list', 'label' => '商品图集', 'rules' => 'trim'],
            ['field' => 'desc', 'label' => '产品描述', 'rules' => 'trim|required',"xss_clean"=>false],
            ['field' => 'limit_num', 'label' => '每日限购数量', 'rules' => 'integer|required'],
            ['field' => 'sku_attr', 'label' => '多规格商品描述信息', 'rules' => 'required'],
            ['field' => 'attrgroup_id', 'label' => '类别id', 'rules' => 'required'],
            ['field' => 'use_end_time', 'label' => '用户使用的到期时间', 'rules' => 'required'],
            ['field' => 'goods_limit', 'label' => '商品每日限购', 'rules' => 'integer|required'],
            ['field' => 'total_limit_num', 'label' => '用户总限购', 'rules' => 'integer|required'],
            ['field' => 'is_limit_open', 'label' => '限购规则', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        //$data['shop_id'] = $this->currentShopId;
        try {
            $ret = (new GoodsBll())->editGoods($this->s_user->aid, $data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('001', '编辑商品错误');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 批量删除商品
     * @author feiying<feiying@iyenei.com>
     */
    public function checked_delete()
    {   
        //验证店铺有效性
        $this->validYdymShop();
        $rule = [
            ['field' => 'id', 'label' => '商品id', 'rules' => 'required']
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        try {
            $ret = (new GoodsBll())->checkedDeleteGoods($this->s_user->aid, $data);
            if ($ret > 0) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '删除失败');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }


    /**
     * 修改一店一码上下架
     *
     * @author feiying<feiying@iyenei.com>
     */
    public function edit_status()
    {   
        //验证店铺有效性
        $this->validYdymShop();
        $rule = [
            ['field' => 'id', 'label' => '商品id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '一店一码状态', 'rules' => 'integer|required'],
            ['field' => 'sku_id', 'label' => '库存id', 'rules' => 'integer'],
            ['field' => 'stock_num', 'label' => '商品库存', 'rules' => 'integer'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new GoodsBll())->editStatus($this->s_user->aid, $data);
        if ($ret > 0) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('002', '无法修改');
        }
    }

    /**
     * 商品条件筛选
     * @author feiying<feiying@iyenei.com>
     */
    public function goods_search()
    {
        $rule = [
            ['field' => 'status', 'label' => '一店一码上下架状态', 'rules' => 'integer'],
            ['field' => 'hcity_status', 'label' => '商品商圈审核状态', 'rules' => 'integer'],
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $data['shop_id'] = $this->currentShopId;
        $data['saas_id'] = self::SAAS_ID;
        $ret = (new GoodsBll())->goodsSearch($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 商品详情
     * @author feiying<feiying@iyenei.com>
     */
    public function goods_detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '商品id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new GoodsBll())->goodsDetail($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 修改商品文字和宣传图
     *
     * @author feiying<feiying@iyenei.com>
     */
    public function edit_poster()
    {   
        //验证店铺有效性
        $this->validYdymShop();
        $rule = [
            ['field' => 'id', 'label' => '商品id', 'rules' => 'integer|required'],
            ['field' => 'poster_url', 'label' => '图片地址', 'rules' => 'trim|required'],
            ['field' => 'poster_title', 'label' => '宣传文字', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new GoodsBll())->editPoster($this->s_user->aid, $data);
        if ($ret > 0) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('002', '无法修改');
        }
    }


}