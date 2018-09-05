<?php
/**
 * Created by PhpStorm.
 * User: shaoyu
 * Date: 2018/4/16
 * Time: 11:23
 */
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainElmBusinessDao;
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainElmCrawlerLogDao;
class Elm_api extends crud_controller
{
    /**
     * 获取商户信息
     */
    function get_business_info()
    {
        $rules = [
            ['field'=>'address','label'=>'地址','rules'=>'trim|required'],
            ['field'=>'latitude','label'=>'纬度','rules'=>'trim|required'],
            ['field'=>'longitude','label'=>'经度','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $f_data = $this->form_data($rules);
        $f_data['latitude'] =$f_data['latitude']*1000000;
        $f_data['longitude'] =$f_data['longitude']*1000000;

//        $latitude_s = $f_data['latitude'] -27000;
//        $latitude_x = $f_data['latitude'] +27000;
//        $longitude_z = $f_data['longitude'] -27000;
//        $longitude_y = $f_data['longitude'] +27000;
//        //查找是否有过附近3km的记录
//        $elm_crawler_log_dao = new elm_crawler_log_dao();
//        $where = " `latitude` between {$latitude_s} and {$latitude_x} and `longitude` between {$longitude_z} and {$longitude_y} and `pc_status`=1";
//        $res = $elm_crawler_log_dao->get_one($where);
//        if($res)
//        {
//            //将这两条爬虫关联
//            $data = ['uid'=>$this->s_user->id, 'address'=>$f_data['address'], 'latitude'=>$f_data['latitude'], 'longitude'=>$f_data['longitude'], 'pc_status'=>2, 'dc_status'=>1,'connect_pc'=>$res->id];
//            $elm_crawler_log_dao->create($data);
//            $this->json_do->set_msg('爬取成功，请从左边导出！');
//            $this->json_do->out_put();
//        }
        //开启爬虫,添加爬取记录并通知前端
        $elmCrawlerLogDao = ManageMainElmCrawlerLogDao::i();
        $data = ['uid'=>$this->s_user->id, 'address'=>$f_data['address'], 'latitude'=>$f_data['latitude'], 'longitude'=>$f_data['longitude'], 'pc_status'=>0, 'dc_status'=>0,];
        $id = $elmCrawlerLogDao->create($data);
        $this->_elm_curl($id,json_encode($f_data));
        $this->json_do->set_msg('开始爬取中，请稍后在左边查看导出！');
        $this->json_do->out_put();
    }

    /**
     * 展示当前用户的爬虫记录
     */
    function show_crawler_log(){
        $uid = $this->s_user->id;
        if (!$uid)
            redirect('/home/index');
        $elmCrawlerLogDao = ManageMainElmCrawlerLogDao::i();
        $res = $elmCrawlerLogDao->getAllArray(['uid'=>$uid]);
        $this->json_do->set_data($res);
        $this->json_do->out_put();
    }

    /**
     * 导出已经爬取的商户信息
     */
    function export_business_info(){
        $id = $this->input->get('id');
        $elmCrawlerLogDao = ManageMainElmCrawlerLogDao::i();
        $res = $elmCrawlerLogDao->getOne(['id'=>$id,'pc_status'=>1]);
        if (!$res)
            $this->json_do->set_error('005','获取数据失败！');
        $elmCrawlerLogDao->update(['dc_status'=>1],['id'=>$id]);
        $elmBusinessDao = ManageMainElmBusinessDao::i();
        $where = ['crawler_id'=>$id];
        $data = $elmBusinessDao->getAllArray($where,'name,address,phone,recent_order_num');
        $this->_export($data);
    }

    /**
     * 导出商家列表
     */
    private function _export($data)
    {
        // 字段，一定要以$data字段顺序
        $fields = [
            'name' => '商家名称',
            'address' => '地址',
            'phone' => '电话',
            'recent_order_num' => '月订单',
        ];
        // 文件名尽量使用英文
        $filename = 'Elm_business_' . mt_rand(100, 999) . '_' . date('Y-m-d');

        ci_phpexcel::down($fields, $data, $filename);
    }

    /**
     * @param $id
     * @param $data
     * 异步开启,不会堵塞当前线程
     */
    private function _elm_curl($id,$data)
    {
        $url = ADMIN_URL . "elm_crawler/crawler_start?id={$id}&data={$data}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_exec($ch);
        curl_close($ch);
    }

    function get_address(){
        $address = urlencode($this->input->get_post('address'));
        $url = "https://h5.ele.me/restapi/bgs/poi/search_poi_nearby?keyword={$address}&offset=0&limit=20";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Host: h5.ele.me",
            "Connection: keep-alive",
            "Accept: */*",
            "Accept-Language: zh-CN,zh;q=0.9",
        ));
        $result= curl_exec($ch);
        $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);// 最后一个收到的HTTP代码
        if($code!='404' && $result){
            $this->json_do->set_data($result);
            $this->json_do->out_put();
        }
        curl_close($ch);
    }



}