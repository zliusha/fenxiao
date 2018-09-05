<?php
/**
 * @Author: binghe
 * @Date:   2017-08-10 10:30:34
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-11-22 15:35:50
 */
require_once ROOT . 'vendor/autoload.php';
use Qiniu\Auth;

/**
 * 七牛
 */
class Qiniu_api extends crud_controller
{

    public $inc;
    public function __construct()
    {
        parent::__construct();
        $this->inc = &inc_config('qiniu');
    }
    /**
     * 得到授权凭证 1小时有效
     * @return [type] [description]
     */
    public function get_token()
    {
        $type = $this->input->get('type');
        //文件归类
        $types=['main_header','wsc_goods','wsc_zx','version'];
        if (empty($type) || !in_array($type, $types)) {
            $this->json_do->set_error('001', 'type参数错误');
        }

        $access_key = $this->inc['access_key'];
        $secret_key = $this->inc['secret_key'];
        $bucket = $this->inc['bucket'];
        // var_dump($this->ini);exit;
        // $key=$type.time().'_'.rand(100,999).'.'.$ext;
        $auth = new Auth($access_key, $secret_key);
        $up_token = $auth->uploadToken($bucket);
        $data['up_token'] = $up_token;
        // $data['key']=$key;
        $data['upload_url'] = 'http://upload.qiniu.com/';
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
