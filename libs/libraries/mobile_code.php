<?php
/**
 * @Author: binghe
 * @Date:   2016-06-06 11:17:11
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-21 17:25:27
 */
class mobile_code
{
    //pwd=>139AE35AE2295AF42771E5BEB270

    public function send($mobiles,$content,$tag='云店宝')
    {
        header("Content-Type: text/html; charset=UTF-8");
  
        $argv = array(
            'name' => '15857512016', //必填参数。用户账号
            'pwd' => '7604D6EE667ED0C0A1D2F6C9C734', //必填参数。（web平台：基本资料中的接口密码）
            'mobile'=>$mobiles,
            'content' => '【'.$tag.'】'.$content, //必填参数。发送内容（1-500 个汉字）UTF-8编码            
            'stime' => '', //可选参数。发送时间，填写时已填写的时间发送，不填时为当前时间发送            
            'type' => 'pt', //必填参数。固定值 pt gx
            'extno' => ''    //可选参数，扩展码，用户定义扩展码，只能为数字
        );

        //构造要post的字符串
        $url = "http://web.duanxinwang.cc/asmx/smsservice.aspx"; //提交的url地址
        $flag = 0; 
        $params='';
        foreach ($argv as $key=>$value) { 
            if ($flag!=0) { 
                $params .= "&"; 
                $flag = 1; 
            } 
            $params.= $key."="; $params.= urlencode($value);// urlencode($value); 
            $flag = 1; 
        }   
        $url = "http://web.duanxinwang.cc/asmx/smsservice.aspx?".$params; //提交的url地址
        $res= file_get_contents($url);  //获取信息发送后的状态
        return substr($res, 0, 1);  //获取信息发送后的状态
    }
}