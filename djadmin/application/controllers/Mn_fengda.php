<?php
/**
 * @Author: binghe
 * @Date:   2017-11-02 17:25:04
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-01-29 11:20:05
 */
/**
* 风达模拟
*/
class Mn_fengda extends CI_Controller
{
    /**
     * 模拟接单
     */
    public function push($trono)
    {
        try{
            $data['tradeno'] = $trono;
            $wm_fengda_bll = new wm_fengda_bll();
            $result = $wm_fengda_bll->push($data);
            var_dump($result);
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }

    }

    /**
     * 模拟取消
     */
    public function cancel($trono)
    {


        try{
            $data['tradeno'] = $trono;
            $wm_fengda_bll = new wm_fengda_bll();
            $result = $wm_fengda_bll->cancel($data);
            var_dump($result);
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * 模拟取消
     */
    public function freight($trono)
    {


        try{
            $data['tradeno'] = $trono;
            $wm_fengda_bll = new wm_fengda_bll();
            $result = $wm_fengda_bll->freight($data);
            var_dump($result);
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * 模拟取消
     */
    public function recharge($trono='')
    {

        try{
            $ci_fengda = new ci_fengda();
            $params['merchantId'] = 1226;
            $params['type'] = 2;
            $params['amount'] = 0.01;
            $params['time'] = time();
            $result = $ci_fengda->request($params, ci_fengda::MERCHANT_RECHARGE);

            var_dump($result);

        }catch(Exception $e) {
            $errMsg = $e->getMessage();
            log_message('error', __METHOD__ . '--' . "风达查询商户信息失败-" . $errMsg);
            throw new Exception($errMsg);
        }
    }

    public function recharge_list($aid)
    {
        try{
            $ci_fengda = new ci_fengda();
            $params['merchantId'] = $aid;
            $params['currentNum'] = 1;
            $params['pageSize'] = 10;
            $result = $ci_fengda->request($params, ci_fengda::MERCHANT_RECHARGE_LIST);

            var_dump($result);

        }catch(Exception $e) {
            $errMsg = $e->getMessage();
            log_message('error', __METHOD__ . '--' . "风达查询商户信息失败-" . $errMsg);
            throw new Exception($errMsg);
        }
    }

    /**
     * 模拟取消
     */
    public function agent_info($trono)
    {


        try{
            try{
                $ci_fengda = new ci_fengda();
                $params['merchantId'] = $trono;
                $result = $ci_fengda->request($params, ci_fengda::AGENT_INFO);

                var_dump($result);

            }catch(Exception $e) {
                $errMsg = $e->getMessage();
                log_message('error', __METHOD__ . '--' . "风达查询商户信息失败-" . $errMsg);
                throw new Exception($errMsg);
            }
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }


    public function test()
    {
        echo 'true';
    }

}