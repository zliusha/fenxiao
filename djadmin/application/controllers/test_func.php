<?php
/**
 * @Author: binghe
 * @Date:   2017-08-09 14:18:24
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-23 14:38:17
 */
use Service\Enum\SaasEnum;
/**
* 测试
*/
class test_func extends base_controller
{
    public function decryption()
    {
        // $str='0acabe2810fe8a155669ba71796e5cf3e4e197fb3d7dd7d0ab8ef377b98a7b6827055f5096f3d1c16924feb441331653b36f9b0f119c6e8f8bb44991e4834d36v7ppzJOcT1tlB2tBTyieb%2FE%2BDpWUhrbir7wZ6eOLGz8NV3s1sRKCeqvJ2kF6rrL8swAnjGQ4wAsagCCIp4pnb1zod0eWLYZGysjmGk7DZlTNfrqtup6DN%2Facvd1gdSSzf5RoqCfVEriVJs3iXDjjKlSOHZq%2B2y24oz68Sx%2B0C%2BDkgQ2zfp4AcUuGT%2FQZaPQn1z7hjGOJBYqTYxRKr665F4oC7GZVUvrY6LsQJhy0dL3mrGinVE50KDod3JKD27nT9XKRWEs1TDs43%2FOsLLnE1%2BKIaXrpJ0zP4jU%2FSdQdrIU%3D";
        $str = '0acabe2810fe8a155669ba71796e5cf3e4e197fb3d7dd7d0ab8ef377b98a7b6827055f5096f3d1c16924feb441331653b36f9b0f119c6e8f8bb44991e4834d36v7ppzJOcT1tlB2tBTyieb%2FE%2BDpWUhrbir7wZ6eOLGz8NV3s1sRKCeqvJ2kF6rrL8swAnjGQ4wAsagCCIp4pnb1zod0eWLYZGysjmGk7DZlTNfrqtup6DN%2Facvd1gdSSzf5RoqCfVEriVJs3iXDjjKlSOHZq%2B2y24oz68Sx%2B0C%2BDkgQ2zfp4AcUuGT%2FQZaPQn1z7hjGOJBYqTYxRKr665F4oC7GZVUvrY6LsQJhy0dL3mrGinVE50KDod3JKD27nT9XKRWEs1TDs43%2FOsLLnE1%2BKIaXrpJ0zP4jU%2FSdQdrIU%3D';
        var_dump('str-'.$str);
        $str = $this->encryption->decrypt(urldecode($str));
        var_dump(unserialize($str));
        var_dump(111111111111111);

        $str1 = get_cookie('saas_user');
        var_dump('str1-'.$str1);
        $str1 = $this->encryption->decrypt($str1);
        var_dump(unserialize($str1));

    }
    public function error()
    {
        throw new Exception("this is a error", 500);
        
    }
    public function scrmsdk()
    {
        //此处采用新的scrm
            $scrm_sdk = new scrm_sdk('scrm_new');
            $params['visit_id']=191380;
            $params['appid'] = 'wxcaa42c6f869b831f';
            $res = $scrm_sdk->getXcxInfo($params);
            var_dump($res);
            if(isset($res['data']['authorizer_appid']))
                return $res;
            else
                throw new Exception("小程序未授权或授权过期");
    }
    public function round()
    {
        echo sprintf('%.2f',4.1);
    }
    public function head()
    {
         $version = $this->input->get_request_header('Version'); 
         echo strtolower($version);
    }
    public function inte()
    {
        $domain='adiida.m.wadao.com';
        $preg = '/^[a-z0-9]+\.[a-z]+\.[a-z]+\.[a-z]+$/';
        // $preg = '/^[a-z0-9\.]{3}[a-z]+$/';
        if(preg_match($preg,$domain))
            echo 'success ' . substr($domain,0,strpos($domain,'.'));
        else
            echo 'error';
    }
    // public function sub_subscribe()
    // {
    //     $erp_sdk = new erp_sdk;
    //     $params=[];
    //     $params[] = '';
    //     $params[] = API_URL.''
    //     $res = $erp_sdk->mnsSubscribe();
    //     var_dump($res);
    // }
    public function erp_subscribe_list()
    {
        $erp_sdk = new erp_sdk;
        $res = $erp_sdk->getSubscribleList();
        var_dump($res);

    }
    public function test_mq()
    {
        $client = stream_socket_client('tcp://121.41.177.151:30003', $errno, $errmsg, 1);
        if(!$client)
            echo $errno.'-'.$errmsg.'<br/>';
        else
        {
            //消息体
            $body['environment'] = ENVIRONMENT;
            $body['time'] = time();

            //须转发用户信息
            $data['to_shop_id'] = 43;
            $data['aid'] = 1226;
            //转发内容
            $content['aid'] = 1226;
            $content['shop_id'] = 43;
            $content['tid'] = '18030511133909';
            $content['api_status'] = 3;
            $content['auto_print'] = true;
            $content['print_type'] = 2; //1云打印,2usb打印

            $data['content'] = json_encode($content);
            $body['data'] = json_encode($data);

            $body['sign'] = simple_auth::getSign($body,WORKERMAN_KEY);
            // 发送数据，注意5678端口是Text协议的端口，Text协议需要在数据末尾加上换行符
            fwrite($client, json_encode($body)."\n");
            // 读取推送结果
            echo fread($client, 8192);
            fclose($client);
        }

        
    }

  
    public function test_qiniu()
    {
        $ci_qiniu = new ci_qiniu();
        $token=$ci_qiniu->upload();
        // $token=':gIpq0GZKLkItF66o8eugHlk8P8s=:eyJzY29wZSI6bnVsbCwiZGVhZGxpbmUiOjE1MDI0NTYwNDN9';
        $key='goods/'.date('ymdhis',time()).'_'.rand(100,999).'.jpg';
        $data['key']=$key;
        $data['token']=$token;
        $this->load->view('test/qiniu',$data);
    }

    public function area()
    {
        header("Content-Type: text/html; charset=gb2312");
        $file = file(ROOT.'area.txt');  
        $files = array();  
        foreach($file as $v){  
          $f = explode('-',$v);  
          $d['id']=str_replace(array("\r\n", "\r", "\n"," "),'、',$f[0]);
          // $f[1]=str_replace(array("\r\n", "\r", "\n"," "),'、',$f[1]);
          //这里用trim()函数去除头尾的空格不起效果，因为是全角格式。所以用功能更强大的正则来去除空格  
          
          $d['name'] = mb_ereg_replace('^(　| )+', '', $f[1]);  
          $d['name']=str_replace("\r\n","",$d['name']);
          // $d['name']=iconv_strlen($d['name'], 'UTF-8');
          array_push($files,$d);  
        }  
        $file_path = ROOT.'area.php';
        $items=var_export($files,true);
        $data= "<?php \n return ".$items."; \n?>";
        file_put_contents($file_path,$data);
        echo '<pre>';  
        var_dump($files);  
    }
    
}