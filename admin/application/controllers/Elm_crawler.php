<?php
/**
 * Created by PhpStorm.
 * User: shaoyu
 * Date: 2018/4/16
 * Time: 17:31
 */
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainElmBusinessDao;
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainElmCrawlerLogDao;

class elm_crawler extends base_controller
{
    private $num = 8;   //每次爬取条数
    private $id;        //爬虫id
    private $address;   //地址
    private $latitude;  //纬度
    private $longitude; //经度
    private $tmp;

    function __construct()
    {
        parent::__construct();
        $this->id = $this->input->get('id');
        $dada = json_decode($this->input->get('data'));
        $this->address = $dada->address;
        $this->latitude = $dada->latitude;
        $this->longitude = $dada->longitude;
        $this->tmp = true;
    }

    function crawler_start(){
        set_time_limit(0);
        $elmCrawlerLogDao = ManageMainElmCrawlerLogDao::i();
        $where = "`address`= '{$this->address}' and `latitude` = {$this->latitude} and `longitude` = {$this->longitude}";
        $res = $elmCrawlerLogDao->getOne($where);
        if (!$res || $res->id != $this->id) {
            //非法请求
            return;
        }
        log_message('error','爬虫开启：ID-'.$this->id.',开始时间：'.time());
        $i = 0;
        $rank_id = '';
        $la = $this->latitude / 1000000;
        $lo = $this->longitude / 1000000;
        while ($this->tmp) {
            try {
                $url = "https://h5.ele.me/restapi/shopping/v3/restaurants?latitude={$la}&longitude={$lo}&offset={$i}&limit={$this->num}&extras[]=activities&extras[]=tags&extra_filters=home&rank_id={$rank_id}&terminal=h5";
//                $url = "https://h5.ele.me/restapi/shopping/v3/restaurants?latitude=30.28182&longitude=120.061031&offset={$i}&limit={$this->num}&extras[]=activities&extras[]=tags&extra_filters=home&rank_id={$rank_id}&terminal=h5";
                //开启爬虫
                $res = $this->_getUrlContent($url);
                if (!$res) {
                    throw new Exception("未爬取到数据");
                }
                log_message('error','爬虫：ID-'.$this->id.',数据爬取中，第'.($i/$this->num +1).'页,时间：'.time());
                //数据截取处理
                $result = $this->_filterStr($res);
                if (!$result->items) {
                    throw new Exception("未爬取到数据");
                }
                $rank_id = $result->meta->rank_id;
                //数据保存
                $this->_saveData($result);
            } catch (Exception $e) {
                //更新爬虫状态为失败
                $elmCrawlerLogDao->update(['pc_status'=> -1],['id'=>$this->id]);
                log_message('error','爬虫失败：ID-'.$this->id. ',原因-'.$e->getMessage().',地址-'.$this->address.',纬度-'.$this->latitude.',经度-'.$this->longitude);
                exit();
            }
            $i+= $this->num;
            sleep(3);
        }
        //更新爬虫状态为成功
        $elmCrawlerLogDao->update(['pc_status'=>1],['id'=>$this->id]);
        log_message('error','爬虫完成：ID-'.$this->id.',结束时间：'.time());
    }

    /**
    * 爬虫程序 -- 原型
    * 从给定的url获取字符串
    * @param string $url
    * @return string
    */
    private function _getUrlContent($url) {
        $ch=curl_init();  //初始化一个cURL会话
        /*curl_setopt 设置一个cURL传输选项*/
        //设置需要获取的 URL 地址
        curl_setopt($ch,CURLOPT_URL,$url);
        //TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //设置https 支持
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //启用时会将头文件的信息作为数据流输出
        curl_setopt($ch,CURLOPT_HEADER,1);
        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Host: h5.ele.me",
            "Connection: keep-alive",
            "Accept: */*",
            "Accept-Language: zh-CN,zh;q=0.9",
            /*'Cookie:_za=4540d427-eee1-435a-a533-66ecd8676d7d; */
        ));
        $result=curl_exec($ch);//执行一个cURL会话
        $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);// 最后一个收到的HTTP代码
        if($code!='404' && $result){
            return $result;
        }
        curl_close($ch);//关闭cURL
    }

    /**
     * 从字符串中筛选json
     * @param string $web_content
     * @return object
     */
    private function _filterStr($web_content) {
        $reg_tag_a = '/\{.+/';
        $result = preg_match_all($reg_tag_a, $web_content, $match_result);
        if ($result) {
            return json_decode($match_result[0][0]);
        }
    }

    /**
     * 数据入库
     * @param object $data
     */
    private function _saveData($data){
        $arr = [];
        $redis = new ci_redis();
        $key = $redis->generate_key('authentic_id');
        foreach ($data->items as $obj) {
            $elm['authentic_id'] = number_format($obj->restaurant->authentic_id,0,'','');
            if ($redis->hget($key,$elm['authentic_id'])) {
                continue;
            } else {
                $redis->hset($key,$elm['authentic_id'],1);
            }
            $elm['name'] = $obj->restaurant->name;
            $elm['address'] = $obj->restaurant->address;
            $elm['phone'] = $obj->restaurant->phone;
            $elm['recent_order_num'] = $obj->restaurant->recent_order_num;
            $elm['latitude'] = round($obj->restaurant->latitude,6);
            $elm['longitude'] = round($obj->restaurant->longitude,6);
            $elm['crawler_id'] = $this->id;
            $elm['time'] = time();
            $arr[] = $elm;
        }
        if (count($arr)<2) {
            $this->tmp = false;
        }
        $elmBusinessDao = ManageMainElmBusinessDao::i();
        $elmBusinessDao->createBatch($arr);
    }

}