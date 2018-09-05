<?php
/**
 * @Author: binghe
 * @Date:   2017-11-02 17:25:04
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 11:15:52
 */
use Service\DbFrame\DataBase\WmMainDbModels\WmDadaCityDao;
/**
* 达达模拟
*/
class Mn_dada extends CI_Controller
{
    public function success()
    {
        $trono='18012911187141';
        // $this->accept($trono);
        // $this->fetch($trono);
        $this->finish($trono);
    }
    /**
     * 模拟接单
     */
    public function accept($trono)
    {
        $url=ci_dada::MN_ACCEPT;
        $data['order_id']=$trono;
        $this->_exeRequest($url,$data);
    }
    /**
     * 模拟取货
     */
    public function fetch($trono)
    {
        $url=ci_dada::MN_FETCH;
        $data['order_id']=$trono;
        $this->_exeRequest($url,$data);
    }
    /**
     * 模拟完成
     */
    public function finish($trono)
    {
        $url=ci_dada::MN_FINISH;
        $data['order_id']=$trono;
        $this->_exeRequest($url,$data);
    }
    /**
     * 模拟取消
     */
    public function cancel()
    {
        $url=ci_dada::MN_CANCEL;
        $data['order_id']='';
        $data['reason']='不想点了';
        $this->_exeRequest($url,$data);
    }
    /**
     * 模拟
     */
    public function expire()
    {
        $url=ci_dada::MN_EXPIRE;
        $data['order_id']='';
        $this->_exeRequest($url,$data);
    }

    /**
     * 获取城市列表
     */
    public function cityCodeList()
    {
        $url=ci_dada::CITY_CODE_LIST;
        $data=[];
        $this->_exeRequest($url,$data);
    }

    /**
     * 获取取消原因
     */
    public function cancelReasons()
    {
        $url=ci_dada::CANCEL_REASONS;
        $data=[];
        $this->_exeRequest($url,$data);
    }

    /*
    同步达达城市
     */
    public function sync_dada_city()
    {
        $url=ci_dada::CITY_CODE_LIST;
        $data=[];
        $obj = ci_dada::init_api($url);
        $reqStatus = $obj->makeRequest($data);
        if (!$reqStatus) {
            //接口请求正常，判断接口返回的结果，自定义业务操作
            if ($obj->getCode() == 0) {
                $city_arr=$obj->getResult();
                $data=[];
                foreach ($city_arr as $city) {
                    $item['city_code']=$city['cityCode'];
                    $item['city_name']=$city['cityName'];
                    array_push($data,$item);
                }
                if($data)
                {
                    $wmDadaCityDao = WmDadaCityDao::i();
                    //清除
                    $wmDadaCityDao->delete('1=1');
                    //重新同步
                    $wmDadaCityDao->createBatch($data);
                    echo '同步达达城市成功'; 
                }
                else
                    echo '同步达达城市失败';
                
                echo '<br/>';
            }else{
                //返回失败
            }
            echo sprintf('code:%s，msg:%s', $obj->getCode(), $obj->getMsg());
        }else{
            //请求异常或者失败
            echo 'except';
        }
    }
    private function _exeRequest($url,$data)
    {
        $obj = ci_dada::init_api($url);
        $reqStatus = $obj->makeRequest($data);
        if (!$reqStatus) {
            //接口请求正常，判断接口返回的结果，自定义业务操作
            if ($obj->getCode() == 0) {
                echo json_encode($obj->getResult());
                echo '<br/>';
            }else{
                //返回失败
            }
            echo sprintf('code:%s，msg:%s', $obj->getCode(), $obj->getMsg());
        }else{
            //请求异常或者失败
            echo 'except';
        }
    }
}