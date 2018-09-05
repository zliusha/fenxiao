<?php
/**
 * 点我达外卖配送
 * @author dadi
 */
class ci_dianwoda
{
    //接口基础地址
    public $api = '';
    // APPkey
    public $appKey = '';
    // Secret
    public $secret = '';

    /*
     * 新增订单
     */
    const ADD_ORDER = '/api/v3/order-send.json';
    /*
     * 取消订单
     */
    const FORMAL_CANCEL = '/api/v3/order-cancel.json';
    /**
     * 订单详情查询
     */
    const ORDER_STATUS_QUERY = '/api/v3/order-get.json';
    /**
     * 运费查询
     */
    const FREIGHT_QUERY = '/api/v3/order-receivable-price.json';

    /*******以下为模拟订单**********/

    /*
     * 模拟接单
     */
    const MN_ACCEPT = '/api/v3/order-accepted-test.json';
    /*
     * 模拟到店
     */
    const MN_ARRIVE = '/api/v3/order-arrive-test.json';
    /*
     * 模拟完成取货
     */
    const MN_FETCH = '/api/v3/order-fetch-test.json';
    /*
     * 模拟完成订单
     */
    const MN_FINISH = '/api/v3/order-finish-test.json';

    /*******以上为模拟订单**********/

    /**
     * 初始化api
     */
    public function __construct()
    {
        if (in_array(ENVIRONMENT, ['development'])) {
            $this->api = 'http://60.191.68.46:43580';
        } else {
            $this->api = 'http://api.dianwoda.com';
        }

        $_config = &ini_config('dianwoda');
        $this->appKey = $_config['app_key'];
        $this->secret = $_config['app_secret'];
    }

    /**
     * 调用接口
     * @param  string $call   调用接口
     * @param  array  $params 请求参数
     * @return json         接口请求结果
     */
    public function call($call, $params)
    {
        // 拼装接口地址
        $url = $this->api . $call;

        // 拼装请求参数
        $params['sig'] = $this->sign($params);
        $params['pk'] = $this->appKey;
        $params['timestamp'] = $this->timestamp();
        $params['format'] = 'json';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $output = curl_exec($ch);
        curl_close($ch);

        // pdump($params);
        // pdump($url);
        // pdump($output);exit;

        $result = json_decode($output, true);

        if (!$result) {
            log_message('error', '点我达接口调用异常 ' . $output);
        }

        return $result;
    }

    // 生成sign
    private function sign($params)
    {
        ksort($params);
        $sign = $this->secret;
        foreach ($params as $key => $val) {
            $sign .= $key . $val;
        }
        $sign .= $this->secret;
        $sign = strtoupper(sha1($sign));
        return $sign;
    }

    // 获取毫秒时间戳
    public function timestamp()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
}
