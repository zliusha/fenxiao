<?php

/**
 * @Author: binghe
 * @Date:   2018-08-08 16:44:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-23 11:01:16
 */
namespace Service\Enum;
/**
 * 小程序模板消息
 */
class XcxTemplateMessageEnum 
{
	//余额变动提醒
	const MONEY_CHANGE = 'money_change';
	//购买成功通知
	const BUY_SUCCESS = 'buy_success';
	//销售成功通知
	const SELL_SUCCESS = 'sell_success';
	//收藏成功通知
	const COLLECT_SUCCESS = 'collect_success';
	//新客访问提醒
	const NEW_CUSTOMER_ACCESS = 'new_customer_access';
	//好友帮助结果通知
	const FRIEND_HELP_RESULT = 'friend_help_result';
}