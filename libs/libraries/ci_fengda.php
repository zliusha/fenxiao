<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/6/20
 * Time: 18:28
 */
use Service\Traits\HttpTrait;

class ci_fengda
{
    use HttpTrait;
    //接口基础地址
    public $api_url = '';

    //新增订单
    const NEW_ORDER = '/qs/newOrder';

    //更新订单
    const UPDATE_ORDER = '/qs/updateOrder';

    //商户充值
    const MERCHANT_RECHARGE = '/qs/merchantRecharge';

    //查询代理商信息
    const AGENT_INFO = '/qs/agentInfo';

    //运费查询
    const TRANSPORT_COST = '/qs/transportCost';

    //商户充值/扣费记录接口
    const MERCHANT_RECHARGE_LIST= '/qs/selectMerchantRecharge';


    /**
     * 初始化api
     */
    public function __construct()
    {
        if (in_array(ENVIRONMENT, ['development', 'testing'])) {
            $this->api_url = 'http://16794ui705.iok.la:36755';
//            $this->api_url = 'http://114.55.12.82:30004/rider';
        } else {
            $this->api_url = 'http://114.55.12.82:30004/rider';
        }
    }

    /**
     * 调用接口
     * @param  string $call   调用接口
     * @param  array  $params 请求参数
     * @return json         接口请求结果
     */
    /**
     * 错误时有异常输出
     * @param array $params
     * @return [type] [description]
     */
    public function request($data = [] ,$url)
    {

        $url = $this->api_url.$url;
        $data['sign'] = $this->_getSign($data);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($ch);
        curl_close($ch);
        $json_result = json_decode($output, true);

        $this->_validResult($json_result);
        return $json_result;

    }

    // 生成sign
    private function _getSign($params)
    {
        ksort($params);
        $data = '';
        foreach ($params as $key => $val) {
            $data .= $key .'='. $val;
        }
        return md5($data.'qs');
    }

    /**
     * 验证结果,中断
     */
    private function _validResult($json_result)
    {

        $err_msg = '风达接口请求异常';
        if (isset($json_result['status']) && $json_result['status'] == 200) {
            return true;
        } else {
            if (isset($json_result['status']) && isset($json_result['msg'])) {
                $err_msg =  $json_result['msg'];
                log_message('error',__METHOD__.'-'.$err_msg);
            }else{
                log_message('error',__METHOD__.'-'.json_encode($json_result));
            }

            throw new Exception($err_msg);

        }
    }
}