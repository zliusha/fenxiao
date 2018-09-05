<?php

/**
 * @Author: binghe
 * @Date:   2018-06-28 19:15:01
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-28 19:18:02
 */
namespace Service\DbFrame\DataBase\MainDbModels;
/**
 * 公司
 */
class MainMobileCodeDao extends BaseDao
{
	/**
     * 得到注册验证码
     * @param  [array] $where array条件
     * @return [stdClass]        [mobile_code]
     */
    function getRegCode($phone)
    {
       if(empty($phone))
        return false;
        $where=array(
            'mobile'=>$phone,
            'type' =>0, //手机注册验证码
            'time >'=>time()- 30 * 60  //半小时内有效 
            );
        $this->db->order_by('id','desc')->limit(1);

        $query=$this->db->where($where)->get($this->tableName);

        return $query->row();
    }

    /**
     * 得到修改密码验证码
     * @param  [array] $where array条件
     * @return [stdClass]        [mobile_code]
     */
    function getUpwdCode($phone)
    {
        if(empty($phone))
        return false;
        $where=array(
            'mobile'=>$phone,
            'type' =>1, //手机修改验证码
            'time >'=>time()- 30 * 60  //半小时内有效 
            );
        $this->db->order_by('id','desc')->limit(1);
        $query=$this->db->where($where)->get($this->tableName);
        return $query->row();
    }
    /**
     * 得到验证码
     * @param  [array] $where array条件
     * @return [stdClass]        [mobile_code]
     */
    function getNormalCode($phone)
    {
        if(empty($phone))
        return false;
        $where=array(
            'mobile'=>$phone,
            'type' =>2, //正常
            'time >'=>time()- 30 * 60  //半小时内有效 
            );
        $this->db->order_by('id','desc')->limit(1);
        $query=$this->db->where($where)->get($this->tableName);
        return $query->row();
    }
	
}