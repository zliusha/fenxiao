<?php
/**
 * @Author: binghe
 * @Date:   2017-08-05 14:42:09
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-02-24 09:54:16
 */
/**
* 校验类
*/
class ci_check
{
    private $CI;
    function __construct()
    {
        $this->CI=&get_instance();
    }
    /**
     * 表单验证
     * @param  array $rule 规则
     * @return [type]       [description]
     */
    function check_form($rule)
    {
        if(empty($rule))
            return false;
        
        if(!isset($this->CI->form_validation))
            $this->CI->load->library('form_validation');
        $this->CI->form_validation->set_rules($rule);
        $this->CI->form_validation->set_error_delimiters('<div class="error">', '</div>');
        if($this->CI->form_validation->run() == FALSE)
        {return false;}
        else
        {return true;}
    }
    /**
     * 表单ajax请求
     * @param  array  $rule 验证规则
     * @param  boolean $exit 默认为ture,出错时输出错误并停止
     * @return [type]        [description]
     */
    function check_ajax_form($rule,$exit=true)
    {
        if(!isset($this->CI->form_validation))
            $this->CI->load->library('form_validation');
        $this->CI->form_validation->set_rules($rule);
        $this->CI->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $json_do = new json_do();
        if($this->CI->form_validation->run() == FALSE)
        {
            if($exit)
                $json_do->set_error('002',validation_errors());
            else return false;
        }
        else
        {
            return true;
        }
    }
}