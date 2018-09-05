<?php
/**
 * @Author: binghe
 * @Date:   2017-08-22 10:51:06
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:26:57
 */
use Service\Cache\VisitIdToAidCache;
/**
* base_controller
*/
class base_controller extends CI_Controller
{
    // public $check = null; //检测
    public $url_class = '';
    public $url_method = '';
    public $url_directory = '';
    public $json_do = null;
    public $check = null;
    function __construct()
    {
        parent::__construct();
        //获取路由中的class 和 method
        if ($this->url_class === '') {
            $this->url_class = strtolower($this->router->class);
        }
        if ($this->url_method === '') {
            $this->url_method = strtolower($this->router->method);
        }
        if ($this->url_directory === '') {
            $this->url_directory = strtolower($this->router->directory);
        }
        $this->check = new ci_check();
        $this->json_do = new json_do();
        
    }
    /**
     * 得到表单数据
     * @param  array $rule 规则
     * @return array       表单值
     */
    public function form_data($rule)
    {
        $data = array();
        foreach ($rule as $r) {
            $xss_clean = true;
            if (isset($r['xss_clean']) && $r['xss_clean'] === false) {
                $xss_clean = false;
            }

            $data[$r['field']] = $this->input->post($r['field'], $xss_clean);
        }
        return $data;
    }
    /**
     * 自动加载aid
     * @return [type] [description]
     */
    protected function convert_visit_id($visit_id)
    {
        $visitIdToAidCache = new VisitIdToAidCache(['visit_id'=>$visit_id]);
        return $visitIdToAidCache->getDataASNX();
    }
    //实现错误方法
    public function onException($e)
    {
        log_message('error',"service error: code-{$e->getCode()},msg-{$e->getMessage()}");
        $this->json_do->set_error('005',$e->getMessage());
    }
    
}