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
class Mn_cantingbao extends CI_Controller
{
    /**
     * 模拟接单
     */
    public function push($trono)
    {
        try{
            $data['tid'] = $trono;
            $data['aid'] = 1226;
            $wm_cantingbao_bll = new wm_cantingbao_bll();
            $result = $wm_cantingbao_bll->push($data);
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
            $data['aid'] = $trono;
            $data['aid'] = 1226;
            $wm_fengda_bll = new wm_fengda_bll();
            $result = $wm_fengda_bll->cancel($data);
            var_dump($result);
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