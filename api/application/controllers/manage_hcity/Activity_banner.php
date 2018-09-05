<?php

/**
 * 门店爆款商品
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/8/3
 * Time: 13:37
 */
use Service\Bll\Hcity\ActivityBannerBll;
use Service\Exceptions\Exception;

class Activity_banner extends hcity_manage_controller
{
    /**
     * 爆款列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {   
        $rule = [
            ['field' => 'region', 'label' => '地区名称', 'rules' => 'trim']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $data = (new ActivityBannerBll())->activityBannerList($fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


    /**
     * 添加活动横幅
     * @author yize<yize@iyenei.com>
     */
    public function add()
    {
        $rule = [
            ['field' => 'name', 'label' => '标题', 'rules' => 'trim|required'],
            ['field' => 'pic_url', 'label' => '图片', 'rules' => 'trim|required'],
            ['field' => 'sort', 'label' => '排序', 'rules' => 'trim|integer'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ActivityBannerBll())->activityBannerAdd($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    

    /**
     * 编辑活动横幅
     * @author yize<yize@iyenei.com>
     */
    public function edit()
    {
        $rule = [
            ['field' => 'id', 'label' => '列表id', 'rules' => 'integer|required'],
            ['field' => 'name', 'label' => '标题', 'rules' => 'trim|required'],
            ['field' => 'pic_url', 'label' => '图片', 'rules' => 'trim|required'],
            ['field' => 'sort', 'label' => '排序', 'rules' => 'trim|integer|required'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        (new ActivityBannerBll())->activityBannerEdit($fdata);
        $this->json_do->out_put();
    }

    /**
     * 删除活动横幅
     * @author yize<yize@iyenei.com>
     */
    public function delete()
    {
        $rule = [
            ['field' => 'id', 'label' => '列表id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        (new ActivityBannerBll())->activityBannerDelete($fdata);
        $this->json_do->out_put();
        
    }


    /**
     * 设置活动横幅
     * @author yize<yize@iyenei.com>
     */
    public function set_img()
    {
        $rule = [
            ['field' => 'id', 'label' => '横幅id', 'rules' => 'integer|required'],
            ['field' => 'detail_pic_url', 'label' => '图片', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ActivityBannerBll())->BannerSetImg($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }










}