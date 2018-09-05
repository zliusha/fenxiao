<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/31
 * Time: 9:56
 */
use Service\Bll\Hcity\PlatformCountBll;
use Service\Exceptions\Exception;

class Index extends hcity_manage_controller
{

    /**
     * 首页统计
     * @author yize<yize@iyenei.com>
     */
    public function index_count()
    {

        $sUser=$this->s_user;
        $data = (new PlatformCountBll())->indexCount($sUser);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }







}