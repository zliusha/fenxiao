<?php

/**
 * @Author: binghe
 * @Date:   2018-08-10 11:22:35
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-10 12:30:28
 */
namespace Service\Enum;
/**
 * 短信模板 
 */
class MessageTemplateEnum
{
	
	/***************通用模板*****************/
	//注册
	const REGISTER = 'template_mobile_reg';
	//修改密码
	const UPDATE_PWD = 'template_mobile_pwd';
	//通用
	const NORMAL = 'template_mobile_normal';
	//自动开通账号，发送初始密码
	const INIT_PWD= 'template_mobile_initpwd';

	/****************老版云店宝***************/
	//云店宝-老版外卖
	const YDB_TRAIT= 'template_mobile_exp';

	/****************城市嘿卡***************/
	const HCITY_MANAGE = 'template_hcity_manage';
}