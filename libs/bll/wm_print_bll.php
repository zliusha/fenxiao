<?php
/**
 * @Author: binghe
 * @Date:   2017-10-30 14:34:46
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-09 10:49:39
 */
use Service\Traits\HttpTrait;
/**
* 打印
*/
class wm_print_bll extends base_bll
{
    const ADD_ORDER_URL='http://open.printcenter.cn:8080/addOrder';
    const QUERY_ORDER_URL='http://open.printcenter.cn:8080/queryOrder';
    const QUERY_PRINT_STATUS_URL='http://open.printcenter.cn:8080/queryPrinterStatus';
    use HttpTrait;

    /**
     * {"responseCode":0,"msg":"服务器接收订单成功","orderindex":"xxxxxxxxxxxxxxxxxx"}
     * @param $url 请求地址
     * @param array $params
     * @return [type] [description]
     */
    public function request($url, $params = [])
    {
        $http = $this->getHttp();
        $response = $http->post($url, $params);
        $json_result = $http->parseJSON($response);
        return $json_result;
    }
    /**
     * $params
     * shop_name varchar 店铺名称
     * order_time int 下单时间
     * goods:[['name':'商品名称','num':1,'price':'1.00'],...] price为单价
     * user:['address':'收货地址','name':'收货人名称','phone':'159xxxxxxx']
     * total_money:100.00　合计支付金额
     * package_money:7.00 餐盒费用
     * freight_money:3.00 配送费
     * other_items:[['name':'xxx','value':xxx],...] //其它扩展项
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function get_order_print_content($params)
    {
        $content='';
        $content.='<CB>云店宝订单</CB><BR>';
        //店铺名称
        if(isset($params['shop_name']))
            $content.="<C>{$params['shop_name']}</C>";
        $content.='<BR>';
        //下单时间
        if(isset($params['order_time']))
            $content.='下单时间: '.date('m-d H:i',$params['order_time']).'<BR>';
        $content .= '********************************<BR>';
        $content.='<BR>';
        $content .= '------------1号口袋-------------<BR>';
        if(isset($params['goods']))
        {
            //三列 20,4,8
            foreach ($params['goods'] as $goods) {
                $content.=$this->padding($goods['name'],20);
                $content.=$this->padding('x'.$goods['num'],4);
                $total_price=sprintf("%.2f",$goods['num'] * $goods['price']);
                $content.=$this->padding($total_price,8,'left');
                $content.='<BR>';
            }
        }
        
        $content .= '--------------其它--------------<BR>';
        //餐盒费
        //两列24,8
        if(isset($params['package_money']))
        {
            $content.=$this->padding('餐盒',24);
            $content.=$this->padding(sprintf("%.2f",$params['package_money']),8,'left');
            $content.='<BR>';
        }
        //配送费
        //两列24,8
        if(isset($params['freight_money']))
        {
            $content.=$this->padding('配送费',24);
            $content.=$this->padding(sprintf("%.2f",$params['freight_money']),8,'left');
            $content.='<BR>';
        }
        //两列24,8
        if(isset($params['other_items']) && is_array($params['other_items']))
        {
            foreach ($params['other_items'] as $item) {
                $content.=$this->padding($item['name'],24);
                $content.=$this->padding(sprintf("%.2f",$item['value']),8,'left');
                $content.='<BR>';
            }
            
        }
        $content .= '********************************<BR>';
        //合计金额
        if(isset($params['total_money']));
        {
            $heji="合计金额: ".sprintf("%.2f", $params['total_money']);

            $content.=$this->padding($heji,32,'left');
            $content.='<BR>';
        }
        $content .= '--------------------------------<BR>';
        //收货信息
        if(isset($params['user']))
        {
            $content.='<B>';
            $content.=$params['user']['address'].'<BR>';
            $content.=$params['user']['name'].'<BR>';
            $content.=$params['user']['phone'].'<BR>';
            $content.='</B>';
        }
        return $content;
    }
    /**
     * 补空
     */
    public function padding($str,$len=32,$pad_type='right')
    {


        //汉字两字节，英文一字节，计算长度
        $strlen=(strlen($str)+mb_strlen($str))/2;
        if($len<=$strlen)
            return $str;
        $padding_str=str_pad(' ', $len-$strlen,' ');
        if($pad_type=='right')
            return $str.$padding_str;
        else 
            return $padding_str.$str;
    }
    
}