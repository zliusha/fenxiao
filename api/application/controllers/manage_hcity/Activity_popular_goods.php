<?php

/**
 * 门店爆款商品
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/19
 * Time: 上午11:43
 */
use Service\Bll\Hcity\ActivityPopularGoodsBll;
use Service\Exceptions\Exception;

class Activity_popular_goods extends hcity_manage_controller
{
    /**
     * 爆款列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'region', 'label' => '地址id', 'rules' => 'trim'],
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $data = (new ActivityPopularGoodsBll())->activityPopularList($sUser,$fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 设置爆款商品
     * @author yize<yize@iyenei.com>
     */
    public function edit_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '商品列表id', 'rules' => 'integer|required'],
            ['field' => 'region', 'label' => '地区id', 'rules' => 'trim'],
            ['field' => 'xc_pic_url', 'label' => '宣传海报', 'rules' => 'trim|required'],
            ['field' => 'sort', 'label' => '排序', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new ActivityPopularGoodsBll())->activityPopularAdd($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 修改爆款商品
     * @author yize<yize@iyenei.com>
     */
    public function edit()
    {
        $rule = [
            ['field' => 'id', 'label' => '商品列表id', 'rules' => 'integer|required'],
            ['field' => 'region', 'label' => '地区id', 'rules' => 'trim'],
            ['field' => 'xc_pic_url', 'label' => '宣传海报', 'rules' => 'trim|required'],
            ['field' => 'sort', 'label' => '排序', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new ActivityPopularGoodsBll())->activityPopularEdit($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 修改爆款商品
     * @author yize<yize@iyenei.com>
     */
    public function delete()
    {
        $rule = [
            ['field' => 'id', 'label' => '商品列表id', 'rules' => 'integer|required'],
            ['field' => 'region', 'label' => '地区id', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new ActivityPopularGoodsBll())->activityPopularDelete($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }










}