<?php
/**
 * @Author: binghe
 * @Date:   2018-03-30 14:27:03
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 09:55:52
 */
use Qiniu\Auth;

/**
 * 七牛
 */
class Qiniu extends auth_controller
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
        $type = $this->input->post_get('type');
        //文件归类
        $types=['main_header','wm_goods','wm_zx','comment_pic','hcity'];
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
        $data['upload_url'] = URL_SCHEME.'upload.qiniup.com/';
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}