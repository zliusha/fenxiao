<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 16:28:52
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-27 14:03:22
 */
/**
 * 付呗
 */
class fubei_sdk 
{
	const GATEWAY = "https://shq-api.51fubei.com/gateway";
    private $config = [];
    public function __construct($config)
    {
        $this->config = $config;
    }
    /**
     * 退款 merchant_order_sn,merchant_refund_sn   第三方订单号,第三方退款号
     */
    public function refund($input)
    {
        $data = [
            "app_id"=>$this->config['app_id'],
            "method"=>"openapi.payment.order.refund",
            "format"=>"json",
            "sign_method"=>"md5",
            "nonce"=>time()
        ];
        $content = [
            "merchant_order_sn"=>$input['merchant_order_sn'],
            "merchant_refund_sn"=>$input['merchant_refund_sn'],
            "refund_money"=>$input['refund_money']
        ];
        $data['biz_content'] = json_encode($content);
        return self::execute($data,$this->config);
    }
	/**
	 * 刷卡支付 merchant_order_sn,auth_code,total_fee
	 * @return [type] [description]
	 */
	public function swip($input)
	{
		$data = [
		    "app_id"=>$this->config['app_id'],
		    "method"=>"openapi.payment.order.swipe",
		    "format"=>"json",
		    "sign_method"=>"md5",
		    "nonce"=>time()
		];
		$content = [
		    "type"=>$input['type'],
		    "merchant_order_sn"=>$input['merchant_order_sn'],
		    "auth_code"=>$input['auth_code'],
		    "total_fee"=>$input['total_fee'],
		    "store_id"=>$this->config['store_id'],
		];
		$data['biz_content'] = json_encode($content);
		return self::execute($data,$this->config);
	}
    /**
     * 自带循环检测 刷卡支付 merchant_order_sn,auth_code,total_fee
     * @return [type] [description]
     */
    public function loopSwip($input)
    {
        $data = [
            "app_id"=>$this->config['app_id'],
            "method"=>"openapi.payment.order.swipe",
            "format"=>"json",
            "sign_method"=>"md5",
            "nonce"=>time()
        ];
        $content = [
            "type"=>$input['type'],
            "merchant_order_sn"=>$input['merchant_order_sn'],
            "auth_code"=>$input['auth_code'],
            "total_fee"=>$input['total_fee'],
            "store_id"=>$this->config['store_id'],
        ];
        try{
            $data['biz_content'] = json_encode($content);
            $res = self::execute($data,$this->config);
            if($res->data->trade_state == 'USERPAYING')
            {
                $queryInput['merchant_order_sn'] = $input['merchant_order_sn'];
                //30秒用户支付时间
                for($i=0;$i<20;$i++)
                {
                    $queryRes = self::query($queryInput);
                    if($queryRes->data->trade_state == 'USERPAYING')
                    {
                        sleep(2);
                        continue;
                    }
                    else
                    {
                        $res->data->trade_state = $queryRes->data->trade_state;
                        break;
                    }

                }
            }
            if($res->data->trade_state != 'SUCCESS')
                throw new Exception("支付失败,请重新支付");

            return $res;
        }catch(Exception $e){
            //关闭支付订单
            if(isset($res->data->order_sn))
            {
                $closeRes = $this->reverse(['order_sn'=>$res->data->order_sn]);
                if($closeRes->result_code !=200)
                    log_message('error',__METHOD__.'-撤销支付订单失败：'.@json_encode($input));
            }

            throw new Exception($e->getMessage());
        }
    }
    /**
     * 订单查询 merchant_order_sn
     * @param  [type] $input [description]
     * @return [type]        [description]
     */
    public function query($input)
    {
        $data = [
            "app_id"=>$this->config['app_id'],
            "method"=>"openapi.payment.order.query",
            "format"=>"json",
            "sign_method"=>"md5",
            "nonce"=>time()
        ];
        $content = [
            "merchant_order_sn"=>$input['merchant_order_sn']
        ];
        $data['biz_content'] = json_encode($content);
        return self::execute($data,$this->config);
    }

    /**
     * 支付订单关闭
     * @param $input
     * @return mixed
     * @throws Exception
     */
    public function close($input)
    {
        $data = [
            "app_id"=>$this->config['app_id'],
            "method"=>"openapi.payment.order.close",
            "format"=>"json",
            "sign_method"=>"md5",
            "nonce"=>time()
        ];
        $content = [
            "order_sn"=>$input['order_sn'],
        ];
        $data['biz_content'] = json_encode($content);
        return self::execute($data,$this->config);
    }

    /**
     * 支付订单撤销
     * @param $input
     * @return mixed
     * @throws Exception
     */
    public function reverse($input)
    {
        $data = [
            "app_id"=>$this->config['app_id'],
            "method"=>"openapi.payment.order.reverse",
            "format"=>"json",
            "sign_method"=>"md5",
            "nonce"=>time()
        ];
        $content = [
            "order_sn"=>$input['order_sn'],
        ];
        $data['biz_content'] = json_encode($content);
        return self::execute($data,$this->config);
    }

	public static function execute($content,$config)
    {
        $content['sign'] = static::generateSign($content,$config['app_secret']);
        $result = static::mycurl(static::GATEWAY,$content);
        $json_result = @json_decode($result);
        if(!$json_result)
            throw new Exception("支付返回失败");
        if($json_result->result_code != 200)
            throw new Exception("支付失败-".$json_result->result_message);
        return $json_result;

    }
    private static function mycurl($url,$params = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);

        if (!empty($params)) {

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        }
        $header = array("content-type: application/json");
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        $reponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }
        curl_close($ch);
        return $reponse;

    }
	private static function generateSign($content,$key)
    {
        return strtoupper(static::sign(static::getSignContent($content).$key));

    }
    private static function getSignContent($content)
    {
        ksort($content);
        $signString = "";
        foreach ($content as $key=>$val){
             if(!empty($val)){
                 $signString .= $key."=".$val."&";
             }
        }
        $signString = rtrim($signString,"&");
        return $signString;

    }
    private static function sign($data)
    {
        return md5($data);
    }
   
}