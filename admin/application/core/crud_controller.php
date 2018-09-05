<?php
/**
 * @Author: binghe
 * @Date:   2016-07-22 16:35:31
 * @Last Modified by:   binghe
 * @Last Modified time: 2016-08-05 19:25:52
 */
/**
* crud 继承此类 类名需要映射model_dao中的 model
*/
class crud_controller extends base_controller
{
    public $model_dao;      
    public $s_user;         //登录
    function __construct()
    {
       parent::__construct();

        
        $this->s_user = $this->session->userdata('s_user');
    }

    //加载grid_list
    function grid_list()
    {
        $vdata= array();
        if(method_exists($this,'_grid_data'))
        {
            $vdata= array_merge($vdata, $this->_grid_data());
        }

        $dir=$this->_get_dir();
     
        if(file_exists(APPPATH.'views/'.$dir.$this->url_class.'_list'.EXT))
            $this->load->view($dir.$this->url_class.'_list',$vdata);
        else
            show_404();
    }
    private function _get_dir()
    {
        $router_crud = &inc_config('admin_router_crud');
        $dir='';
        foreach ($router_crud as $key => $crud) {
           foreach ($crud as $v) {
              if($v == $this->router->class)
              {
                $dir=$key.'/';
               return $dir;
              }
           }
        }
        return $dir;
    }

}