<?php
/**
 * @Author: binghe
 * @Date:   2016-07-11 11:25:46
 * @Last Modified by:   binghe
 * @Last Modified time: 2016-11-12 09:47:21
 */
/**
* Acl 访问控制列表（Access Control List，ACL）
* 登录以及权限控制中心
*/
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainSysPoweritemDao;
class Acl
{
    private $CI;        
    private $_pruviews;     //权限
    private $_url_class;    //控制器
    private $_url_method;   //方法
    private $_is_auto_load=false; //是否是自动加载文件
    function __construct()
    {
       $this->CI= &get_instance();
       $this->_url_class = $this->CI->router->class;
       $this->_url_method = $this->CI->router->method;
       //对目录文件的处理
        $_index=strpos($this->_url_method,'-');

        if($_index!==false)
            $this->_is_auto_load=true;
    }
    //hook
    public function auth()
    {
        $s_user_do=$this->CI->session->s_user;

        //已登录
        if($s_user_do)
        {
            
            $no_pro_module = array('welcome','passport','passport_api','common_file');
            //不需要保护的class
            if(in_array($this->_url_class,$no_pro_module))
                return;
            //检测权限
            //1.超级管理员拥有全部权限
            if($s_user_do->is_super_admin)
                return;
            //2.自动加载的方法
            if($this->_is_auto_load)
                return;
            //2.检测是否受保护
            $sysPoweritemDao = ManageMainSysPoweritemDao::i();
            $is_pro=$sysPoweritemDao->isProFun("{$this->_url_class}/{$this->_url_method}"); //可能有多个
            if($is_pro)//受保护
            {
                foreach($s_user_do->power as $item)
                {
                    $index = strpos($item->pro_funs, "{$this->_url_class}/{$this->_url_method}");
                    if($index!==false)
                        return;
                }
                
            }
            else//不受保护
            {
                foreach($s_user_do->power as $item)
                {
                    if(!empty($item->module))
                    {
                        $modules= explode(',',$item->module);
                        foreach($modules as $m)
                        {
                            //不受保护有此模块权限就有权限
                            if($m == $this->_url_class)
                                return;
                        }
                    }
                    
                }
            }
            $this->_no_power();
        }
        else //未登录
        {
            //公开的类
            $not_auth_classes = array('passport','passport_api','machine','curl', 'job','elm_crawler');

            if(!in_array($this->_url_class,$not_auth_classes))
            {
                redirect(site_url('passport/login'));
            }

        }
    }
    private function _no_power()
    {
        if(is_ajax())
        {
            $json_do = new json_do();
            $json_do->set_error('403');
        }
        else
        {
           echo '没有权限!';
        }
        exit;
    }


}