<?php
/**
 * @Author: binghe
 * @Date:   2016-08-19 14:51:36
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-02 18:35:21
 */
/**
* 业务逻辑层
*/
class base_bll 
{
    

    function __construct()
    {
        
    }
    public function __get($name)
    {

        $ci = &get_instance();
        if($name=='db')
        {
            throw new Exception("bll no db");
            
        }
        else
        return $ci->$name;
    }
}