<?php
/**
 * @Author: binghe
 * @Date:   2017-08-18 10:31:13
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-03 16:52:51
 */
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Service\Support\Http;

/**
 * 测试
 */
class Test extends base_controller
{
    public $access_token = '8_HKynOuebDr7MVjudaijWtYZomCfFB1bxrxuPR9kw7RrspswjYFItVhc4dOptwfRKbMYdEH0zFj550Bahx3Djrfe-qMWTblUj0d5mfnWFGCdqIaJoU_f_riAfvUwmOOgPGPXMalhth_bOpcriLNJfAHDFHD';
    public $authorizer_appid = 'wx241426c31d6d51f1';
    public function __construct()
    {
        parent::__construct();
        $scrm_sdk = new scrm_sdk;
        //0外卖,1点餐
        $params['type'] = 0;
        $params['visit_id'] = 210333;
        // $params['visit_id']=191380;
        $json = $scrm_sdk->getXcxInfo($params);
        $this->access_token=$json['data']['authorizer_access_token'];
        $this->authorizer_appid=$json['data']['authorizer_appid'];
    }

    /**
     * 初始化小程序信息
     * @return [type] [description]
     */
    public function init_xcx()
    {
        $scrm_sdk = new scrm_sdk;
        $params['visit_id']=195025;
        $json = $scrm_sdk->getXcxInfo($params);
        $this->access_token=$json['data']['authorizer_access_token'];
        $this->authorizer_appid=$json['data']['authorizer_appid'];
    }
    public function putconent()
    {
        $filepath = UPLOAD_PATH .' /xcx/qrcode/'.time().'.png';
        $string = '1111111';
        $res = write_file($filepath,$string);
        echo $filepath;
        var_dump($res);
    
    }
    public function getQrcode()
    {
        header('content-type:image/gif');
        $this->init_xcx();
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$this->access_token;
        $params['scene'] = 't=1';
        $params['page'] = 'pages/home/index/index';
        $http = new Http();
        $res = $http->post($url,json_encode($params))->getBody()->getContents();
        $filepath = UPLOAD_PATH .time().'.png';
        file_put_contents($filepath, $res);
        echo $res;

    }
    public function intc()
    {
        $items[]=['shop_id'=>1];
        var_dump($items);
    }
    public function testSetWebviewDomain()
    {
        $xcx_sdk = new xcx_sdk;
        //2.提交域名
        $xcx_inc = inc_config('xcx');

        $input=[
            'action'=>'set'
            ,'webviewdomain'=>$xcx_inc['webviewdomain']
        ];
        $res=$xcx_sdk->wxaSetWebviewDomain($input,$this->access_token);

        var_dump($res);
    }
    public function testModifyDomain()
    {
        $xcx_sdk = new xcx_sdk;
        //2.提交域名
        $xcx_inc = inc_config('xcx');
        // var_dump($xcx_inc);exit;
        $input=[
            'action'=>'set'
            ,'requestdomain'=>$xcx_inc['requestdomain']
            ,'wsrequestdomain'=>$xcx_inc['wsrequestdomain']
            ,'uploaddomain'=>$xcx_inc['uploaddomain']
            ,'downloaddomain'=>$xcx_inc['downloaddomain']
        ];
        $res=$xcx_sdk->wxaModifyDomain($input,$this->access_token);

        var_dump($res);
    }
    public function testGetLatestAuditstatus()
    {
        $xcx_sdk = new xcx_sdk;
        $res=$xcx_sdk->getLatestAuditstatus($this->access_token);
        var_dump($res);
    }
    public function testRelease()
    {
        // $scrm_sdk = new scrm_sdk;
        // $params['visit_id']=208070;
        // $json = $scrm_sdk->getXcxInfo($params);
        // $this->access_token=$json['data']['authorizer_access_token'];
        // $this->authorizer_appid=$json['data']['authorizer_appid'];
        // echo $this->access_token;
        // echo '<br/>';
        $xcx_sdk = new xcx_sdk;
        $res=$xcx_sdk->release($this->access_token);
        var_dump($res);
    }
    //版本退回,一天只能退回一次
    public function testUndoCodeAudit()
    {
        $xcx_sdk = new xcx_sdk;
        $res=$xcx_sdk->undoCodeAudit($this->access_token);
        var_dump($res);
    }
    public function testGetAuditstatus()
    {
        //审核状态，其中0为审核成功，1为审核失败，2为审核中
        $xcx_sdk = new xcx_sdk;
        $input=[
        "auditid"=>429006455
        ];
        $res=$xcx_sdk->getAuditstatus($input,$this->access_token);
        var_dump($res);
    }
    public function testSubmitAudit()
    {
        $xcx_sdk = new xcx_sdk;
        $input=[
        "item_list"=>
            [
                ["address"=>'pages/home/home/home',
                "tag"=>"美食 外卖",
                "first_class"=>"餐饮",
                "second_class"=>"菜谱",
                "title"=>"首页"
                ]
            ]
        ];
        $res=$xcx_sdk->submitAudit($input,$this->access_token);
        var_dump($res);
    }
    public function testGetCategory()
    {
        $xcx_sdk = new xcx_sdk;
        $res=$xcx_sdk->getCategory($this->access_token);
        if(!isset($res['category_list'][0]))
                echo '失败<br/>';
        else
            echo '成功<br/>';
        var_dump($res);
    }
    public function testGetPage()
    {
        $xcx_sdk = new xcx_sdk;
        $res=$xcx_sdk->getPage($this->access_token);
        var_dump($res);
    }
    public function testQrCode()
    {
        $xcx_sdk = new xcx_sdk;
        $res=$xcx_sdk->getQrCode($this->access_token);
        var_dump($res);
    }
    public function testWxCommit()
    {
        $xcx_sdk = new xcx_sdk;
        $xcx_inc = &inc_config('xcx');
        $json_arr['extEnable']=true;
        $json_arr['extAppid']=$this->authorizer_appid;
        $json_arr['ext']=[
        'extAppid'=>$this->authorizer_appid
        ,'base_url'=>$xcx_inc['base_url']
        ,'upload_url'=>$xcx_inc['upload_url']
        ,'environment'=>ENVIRONMENT
        ];
        $ext_json = json_encode($json_arr,JSON_UNESCAPED_UNICODE);
        $input1=[
            'template_id'=>9
            ,'user_version'=>'1.1.8'
            ,'user_desc'=>'测试版Beta'
            ,'ext_json'=>$ext_json
        ];

        $res=$xcx_sdk->wxaCommit($input1,$this->access_token);
        var_dump($res);
    }
    public function test_wxInfo()
    {
        $scrm_sdk = new scrm_sdk;
        // $params['visit_id']=191380;
        $params['visit_id']=205334;
        $json = $scrm_sdk->getXcxInfo($params);
        var_dump($json);
    }
    private function setErrorHandler()
    {
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            echo 'p-'.error_reporting();
            echo  '<br/>error-'.$errno;
            echo '<br/>end<br/>';
            if (!(error_reporting() & $errno)) {
                // This error code is not included in error_reporting, so let it fall
                // through to the standard PHP error handler
                return false;
            }

            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        }, E_ALL);
    }
    private function unsetErrorHandler()
    {
        restore_error_handler();
    }

   
    public function get_user_info($mobile)
    {
        
        $erp_sdk = new erp_sdk;
        $params[]=$mobile;
        $res = $erp_sdk->getUserByPhone($params);
        var_dump($res);
    }
    public function clientTest()
    {
        $client = new Client();
        $url='http://fw.waimai.com/test/te';
        $params['sign']='sign';
        $params['json']='json';
        $options['form_params']=$params;
        
        $promise1 = $client->post($url,$options);
        $promise2 = $client->post($url,$options);
        // $promises=[$promise1,$promise2];
        // $results = Promise\unwrap($promises);
        log_message('error',__METHOD__.'--11111111');
    }
    public function te()
    {
        $json = $this->input->post('json');
        $sign = $this->input->post('sign');
        log_message('error',__METHOD__.'--'.$sign.'-'.$json);
        sleep(10);
        echo 'success';
    }
    public function app()
    {
        $json = $this->input->post('json');
        $sign = $this->input->post('sign');
        log_message('error',__METHOD__.'--'.$sign.'-'.$json);
        // sleep(10);
        echo 'success';
    }
    public function mq()
    {
        $redis = new ci_redis();
        $key=$redis->generate_key('mq_list');
        $mq_msg_do = new mq_msg_do;
        $mq_msg_do->type='order_msg';
        $mq_msg_do->data=['tid'=>111];
        $redis->rPush($key,serialize($mq_msg_do));
        echo $redis->lSize($key);

    }

 

    public function test_replace()
    {
        $str='{mobile}-{code}';
        $str=str_replace(['{mobile}','{code}'],['15988844391','123456'],$str);
        echo $str;
    }
    /**
     * 打印机测试
     * @return [type] [description]
     */
    public function print_order()
    {
        $url = 'http://open.printcenter.cn:8080/addOrder';
        $params['deviceNo'] = 'kdt2100749';
        $params['key'] = '008c6';
        $content = $this->_get_print_content();
        $params['printContent'] = $content;
        $params['times'] = 2;

        $http = $this->getHttp();
        $response = $http->post($url, $params);
        $json_result = $http->parseJSON($response);
        var_dump($json_result);
    }
    public function _get_print_content()
    {
        $content = '';
        $content .= '云店宝-一休团队<BR>';
        $content .= "--------------------------------<BR>";
        $content .= '<L>';
        $content .= "饭　　　　　　 1.0    1   1.0<BR>";
        $content .= "炒饭　　　　　 10.0   10  10.0<BR>";
        $content .= "蛋炒饭　　　　 10.0   10  100.0<BR>";
        $content .= "鸡蛋炒饭　　　 100.0  1   100.0<BR>";
        $content .= "番茄蛋炒饭　　 1000.0 1   100.0<BR>";
        $content .= "西红柿蛋炒饭　 1000.0 1   100.0<BR>";
        $content .= "西红柿鸡蛋炒饭 100.0  10  100.0<BR>";
        $content .= "备注：加辣<BR>";
        $content .= '</L>';
        $content .= "--------------------------------<BR>";
        $content .= "合计：xx.0元<BR>";
        $content .= "送货地点：北京市海淀区xx路xx号<BR>";
        $content .= "联系电话：15988844391<BR>";
        $content .= "订餐时间：2015-09-09 09:08:08<BR>";
        return $content;
    }
    //订单打印
    public function print_order2()
    {
        $wm_print_bll = new wm_print_bll;
        $params['deviceNo'] = 'kdt2100749';
        $params['key'] = '008c6';
        $c_params['shop_name'] = '把愚便当(西溪店)';
        $c_params['total_money'] = 12.30;
        $c_params['order_time'] = 18721005592;
        $c_params['goods'] = [
            ['name' => '米饭', 'num' => 3, 'price' => 2],
            ['name' => '手撕鸡a', 'num' => 1, 'price' => 48],
        ];
        $c_params['user'] = ['address' => '公元里7幢6楼', 'name' => '冰河', 'phone' => '15988844391'];
        $c_params['package_money'] = 7.00;
        $c_params['freight_money'] = 3.00;
        $content = $wm_print_bll->get_order_print_content($c_params);
        // echo $content;exit;
        $params['printContent'] = $content;
        $params['times'] = 1;
        $result_json = $wm_print_bll->request(wm_print_bll::ADD_ORDER_URL, $params);
        var_dump($result_json);
    }
    
   
    //dada测试 发单
    public function test_dada()
    {
        //初始化达达商户api
        $source_id='73753';
        $obj = ci_dada::init_api(ci_dada::ADD_ORDER_URL,$source_id);

        //发单数据
        $data = array(
            'shop_no' => '11047059', //固定测试账号 门店编号
            'origin_id' => '201711021755126',
            'city_code' => '021',       //门店订单所属区域
            'cargo_price' => 10,        //价格
            'is_prepay' => 0,           //固定值
            'expected_fetch_time' => 1509625962,//期望取货时间 =当前时间+备货时间
            'receiver_name' => '测试',
            'receiver_address' => '上海市崇明岛',
            'receiver_lat' => 31.63,
            'receiver_lng' => 121.41,
            'receiver_phone' => '18588888888',
            'callback' => 'http://fw.waimaishop.com/notify_dada/index/1226', //1226为{$aid}
        );

        $reqStatus = $obj->makeRequest($data);
        if (!$reqStatus) {
            //接口请求正常，判断接口返回的结果，自定义业务操作
            if ($obj->getCode() == 0) {
                echo json_encode($obj->getResult());
                echo '<br/>';
            } else {
                //返回失败
            }
            echo sprintf('code:%s，msg:%s', $obj->getCode(), $obj->getMsg());
        } else {
            //请求异常或者失败
            echo 'except';
        }
    }
    public function encryption()
    {
        if(!isset($this->encryption))
            $this->load->library('encryption');
        $plain_text='123456';
        $en_str=$this->encryption->encrypt($plain_text);
        echo '<br/>'.$en_str;
        $de_str = $this->encryption->decrypt($en_str);
        echo '<br/>'.$de_str;
    }
    public function phpinfo()
    {
        phpinfo();
    }

    
}
