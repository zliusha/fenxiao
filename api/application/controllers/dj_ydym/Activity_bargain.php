<?php

/**
 * 砍价活动商圈
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/9/3
 * Time: 下午14:11
 */
use Service\Bll\Hcity\ActivityBargainBll;
use Service\Exceptions\Exception;

class Activity_bargain extends dj_ydym_controller
{
    /**
     * 添加集赞商品列表
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午14:17
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
        $ret = (new ActivityBargainBll())->goodsList($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 创建活动砍价活动
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午14:36
     */
    public function create()
    {
        //验证店铺有效性
        $this->validYdymShop();
        $rules = [
            ['field' => 'goods_id', 'label' => '商品id', 'rules' => 'trim|required'],
            ['field' => 'title', 'label' => '商品标题', 'rules' => 'trim|required'],
            ['field' => 'pic_url', 'label' => '商品图集', 'rules' => 'trim'],
            ['field' => 'start_price', 'label' => '起始价格', 'rules' => 'trim|required'],
            ['field' => 'stock_num', 'label' => '活动库存', 'rules' => 'required'],
            ['field' => 'start_time', 'label' => '活动开始时间', 'rules' => 'required'],
            ['field' => 'end_time', 'label' => '活动结束时间', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        $data['shop_id'] = $this->currentShopId;
        $data['saas_id'] = self::SAAS_ID;
        $ret = (new ActivityBargainBll())->createActivityKj($this->s_user->aid, $data);
        if ($ret) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('001', '新增活动错误');
        }
    }
   
   /**
     * 编辑砍价活动
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午15:00
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
            ['field' => 'start_price', 'label' => '起始价格', 'rules' => 'trim|required'],
            ['field' => 'stock_num', 'label' => '活动库存', 'rules' => 'required'],
            ['field' => 'start_time', 'label' => '活动开始时间', 'rules' => 'required'],
            ['field' => 'end_time', 'label' => '活动结束时间', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        try {
            $ret = (new ActivityBargainBll())->editActivityKj($this->s_user->aid, $data);
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
     * 删除砍价活动
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午15:19
     */
    public function delete()
    {   
         $rule = [
            ['field' => 'id', 'label' => '活动id', 'rules' => 'required']
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        try {
            $ret = (new ActivityBargainBll())->deleteActivityKj($this->s_user->aid, $data);
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
     * 添加砍价活动商品列表
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午15:23
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
        $ret = (new ActivityBargainBll())->ActivityListKj($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 一点一码砍价活动上架
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午18:47
     */
    public function apply_status()
    {   
        //验证店铺有效性
        $this->validYdymShop();
        $rule = [
            ['field' => 'id', 'label' => '活动id', 'rules' => 'integer|required'],
            ['field' => 'audit_status', 'label' => '状态', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityBargainBll())->applyStatusKjYdym($this->s_user->aid, $data);
        if ($ret > 0) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('002', '无法修改');
        }
    }

    /**
     * 砍价活动详情
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午18:47
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '助力活动状态', 'rules' => 'integer']
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityBargainBll())->ActivityDetailKj($data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 砍价活动详情轮次
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/4
     * Time: 下午16:47
     */
    public function activityturns_list()
    {
        $rule = [
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityBargainBll())->activityturnsList($data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

}