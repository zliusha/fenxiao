<?php
/**
 * @Author: binghe
 * @Date:   2017-11-02 14:26:01
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-01-29 11:15:23
 */
require_once LIBS_PATH . 'libraries/dada/DadaOpenapi.php';
/**
* 达达外卖配送
*/
class ci_dada
{
    /*
    新增订单
     */
    const ADD_ORDER='/api/order/addOrder';
    /*
    重新发单
     */
    const RE_ADD_ORDER='/api/order/reAddOrder';
    /*
    取消订单
     */
    const FORMAL_CANCEL='/api/order/formalCancel';
    /*
    取消订单原因列表
     */
    const CANCEL_REASONS='/api/order/cancel/reasons';
    /**
     * 订单详情查询
     */
    const ORDER_STATUS_QUERY='/api/order/status/query';
    /*
    城市
     */
    const CITY_CODE_LIST='/api/cityCode/list';

    /*******以下为模拟订单**********/

    /*
    模拟接单
     */
    const MN_ACCEPT='/api/order/accept';
    /*
    模拟完成取货
     */
    const MN_FETCH='/api/order/fetch';
    /*
    模拟完成订单
     */
    const MN_FINISH='/api/order/finish';
    /*
    模拟取消订单
     */
    const MN_CANCEL='/api/order/cancel';
    /*
    模拟订单过期
     */
    const MN_EXPIRE='/api/order/expire';

    /*******以上为模拟订单**********/




    /**
     * 初始化api
     * @param  string $url       api接口
     * @param  string $source_id 商户编号,非正式环境下自动转为测试商户编号73753 11047059门店编号
     * @return object DadaOpenapi            api对象
     */
    public static function init_api($url,$source_id='73753')
    {
        $config = array();
        $data_ini = &ini_config('dada');
        $config['app_key'] = $data_ini['app_key'];
        $config['app_secret'] = $data_ini['app_secret'];
        $domain='newopen.imdada.cn';
        if(ENVIRONMENT!='production' || $source_id=='73753')
        {
            $source_id='73753';
            $domain='newopen.qa.imdada.cn';
        }
        $config['source_id']=$source_id;
        $config['url'] = 'http://'.$domain.$url;
        $obj = new DadaOpenapi($config);
        return $obj;
    }
}