<?php

/**
 * 集赞活动一店一码
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/8/19
 * Time: 上午11:40
 */
use Service\Bll\Hcity\ActivityGoodsJzBll;
use Service\Exceptions\Exception;

class Activity_goods_jz extends dj_ydym_controller
{

    /**
     * 创建活动
     * @author feiying<feiying@iyenei.com>
     */
    public function create()
    {
        //验证店铺有效性
        $this->validYdymShop();
        $rules = [
            ['field' => 'goods_id', 'label' => '商品id', 'rules' => 'trim|required'],
            ['field' => 'title', 'label' => '商品标题', 'rules' => 'trim|required'],
            ['field' => 'pic_url', 'label' => '商品图集', 'rules' => 'trim'],
            ['field' => 'price', 'label' => '市场价', 'rules' => 'trim|required'],
            ['field' => 'activity_price', 'label' => '活动价', 'rules' => 'trim|required'],
            ['field' => 'stock_num', 'label' => '活动库存', 'rules' => 'required'],
            ['field' => 'start_time', 'label' => '活动开始时间', 'rules' => 'required'],
            ['field' => 'end_time', 'label' => '活动结束时间', 'rules' => 'integer|required'],
            ['field' => 'need_help_num', 'label' => '用户总限购', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        $data['shop_id'] = $this->currentShopId;
        $data['saas_id'] = self::SAAS_ID;
        $ret = (new ActivityGoodsJzBll())->createActivity($this->s_user->aid, $data);
        if ($ret) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('001', '新增活动错误');
        }
    }

    /**
     * 编辑活动
     * @author feiying<feiying@iyenei.com>
     */
    public function edit()
    {   
        //验证店铺有效性
        $this->validYdymShop();
        $rule = [
            ['field' => 'id', 'label' => '活动id', 'rules' => 'trim|required'],
            ['field' => 'goods_id', 'label' => '商品id', 'rules' => 'trim|required'],
            ['field' => 'title', 'label' => '商品标题', 'rules' => 'trim|required'],
            ['field' => 'pic_url', 'label' => '商品图集', 'rules' => 'trim'],
            ['field' => 'price', 'label' => '市场价', 'rules' => 'trim|required'],
            ['field' => 'activity_price', 'label' => '活动价', 'rules' => 'trim|required'],
            ['field' => 'stock_num', 'label' => '活动库存', 'rules' => 'required'],
            ['field' => 'start_time', 'label' => '活动开始时间', 'rules' => 'required'],
            ['field' => 'end_time', 'label' => '活动结束时间', 'rules' => 'integer|required'],
            ['field' => 'need_help_num', 'label' => '用户总限购', 'rules' => 'integer|required'],
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityGoodsJzBll())->editActivity($this->s_user->aid, $data);
        try {
            $ret = (new ActivityGoodsJzBll())->editActivity($this->s_user->aid, $data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('001', '活动编辑商品错误');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }        
    }

    /**
     * 删除活动
     * @author feiying<feiying@iyenei.com>
     */
    public function delete()
    {   
         $rule = [
            ['field' => 'id', 'label' => '活动id', 'rules' => 'required']
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        try {
            $ret = (new ActivityGoodsJzBll())->deleteActivity($this->s_user->aid, $data);
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
     * 添加活动商品列表
     * @author feiying<feiying@iyenei.com>
     */
    public function goods_list()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|integer'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $data['shop_id'] = $this->currentShopId;
        $data['saas_id'] = self::SAAS_ID;
        $ret = (new ActivityGoodsJzBll())->goodsList($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 助力活动列表
     * @author feiying<feiying@iyenei.com>
     */
    public function activity_list()
    {
        $rule = [
            ['field' => 'status', 'label' => '助力活动状态', 'rules' => 'integer'],
            ['field' => 'audit_status', 'label' => '助力审核状态', 'rules' => 'integer'],
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $data['shop_id'] = $this->currentShopId;
        $data['saas_id'] = self::SAAS_ID;
        $ret = (new ActivityGoodsJzBll())->ActivityList($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

     /**
     * 助力活动详情
     * @author feiying<feiying@iyenei.com>
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '助力活动状态', 'rules' => 'integer']
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityGoodsJzBll())->ActivityDetail($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 申请助力活动上架
     *
     * @author feiying<feiying@iyenei.com>
     */
    public function apply_status()
    {   
        //验证店铺有效性
        $this->validYdymShop();
        $rule = [
            ['field' => 'id', 'label' => '活动id', 'rules' => 'integer|required'],
            ['field' => 'audit_status', 'label' => '一店一状态', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityGoodsJzBll())->applyStatusYdym($this->s_user->aid, $data);
        if ($ret > 0) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('002', '无法修改');
        }
    }
    
    /**
     * 助力活动用户列表
     * @author feiying<feiying@iyenei.com>
     */
    public function activityuser_list()
    {
        $rule = [
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '助力活动状态', 'rules' => 'integer|required'],
            ['field' => 'audit_status', 'label' => '助力活动状态', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityGoodsJzBll())->activityuserList($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

}