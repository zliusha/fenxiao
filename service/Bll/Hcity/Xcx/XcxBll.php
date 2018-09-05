<?php

/**
 * @Author: binghe
 * @Date:   2018-08-07 14:45:56
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-24 11:17:31
 */
namespace Service\Bll\Hcity\Xcx;
use Service\Exceptions\Exception;
use Service\Sdk\XcxSdk;
use Service\Cache\XcxAccessTokenCache;
use Service\Enum\XcxTemplateMessageEnum;
/**
 * 小程序业务
 */
class XcxBll extends \Service\Bll\BaseBll
{
	private $templateMap;
	private $config;
	private $xcxSdk = null;
	public function __construct()
	{
		parent::__construct();
		$config = &inc_config('xcx_hcity');
		$this->config = $config;
		$this->templateMap = $config['template_map'];
	}
	public function getSdk()
	{
		if($this->xcxSdk === null)
			$this->xcxSdk = new XcxSdk(['appid'=>$this->config['app_id'],'app_secret'=>$this->config['app_secret']]);
		return $this->xcxSdk;
	}
	/**
	 * 获取小程序 accessToken
	 * @return [type] [description]
	 */
	public function getAccessToken()
	{
		$xcxAccessTokenCache = new XcxAccessTokenCache(['appid'=>$this->config['app_id'],'app_secret'=>$this->config['app_secret']]);
		return $xcxAccessTokenCache->getDataASNX();
	}
	/**
	 * 获取小程序二维码
	 * @param  array  $params 必填:scene,page,width
	 * @param strig $dirPath 保存本地绝对路径
	 * @param string $fileName 文件名称(需带扩展)
	 * @return string         本地图片相对路径
	 * @author binghe 2018-08-20
	 */
	public function getQrcodeStreamContents(array $params,string $dirPath,string $fileName = null)
	{
		$accessToken = $this->getAccessToken();
		$xcxSdk = $this->getSdk();
		
		if(!$fileName)
			$fileName = md5(create_guid()).'.png';
		//创建目录
		$absDirPath = UPLOAD_PATH.$dirPath;
		if(!is_dir($absDirPath))
                mkdir($absDirPath,0700,true);
        //文件绝对路径
		$absFilePath = $absDirPath.$fileName;

		//小程序二维码流
		$contents = $xcxSdk->getQrcodeStreamContents($params,$accessToken);

		file_put_contents($absFilePath, $contents);
		return $dirPath.$fileName;

	}
	/**
	 * 发送未封装的模板消息
	 * @param  array  $params [description]
	 * @return [type]         [description]
	 */
	public function sendTemplateMessage(array $params)
	{
		$accessToken = $this->getAccessToken();
		$data = [
    		'template_id'=>$params['template_id'],
			'touser'=>$params['touser'],
			'page'=> isset($params['page'])?$params['page']:'',
			'form_id'=>$params['form_id'],
			'data'=>$params['data'],
			'emphasis_keyword'=>isset($params['emphasis_keyword'])?$params['emphasis_keyword']:''
    	];
    	$xcxSdk = $this->getSdk();
		$xcxSdk->sendTemplateMessage($data,$accessToken);
		return true;
	}
	/**
	 * 消息入队工厂
	 * @param  array  $params       参数 工厂参数 openid,page_params=[]
	 * @param  string $templateName 模板名称
	 * @return [type]               [description]
	 * @author binghe 2018-08-08
	 */
	public function pushMessageFac(array $params,string $templateName)
	{
		if(!isset($this->templateMap[$templateName]))
			throw new Exception('消息模板不存在');
		if(!isset($params['openid']))
			throw new Exception('openid不存在');
		$state = $this->templateMap[$templateName];
		$page = $state['page'];
		//页面存在　且　数组　且　不为空
		if($page && is_array($params['page_params']) && $params['page_params'])
		{
			$page.='?'.create_linkstring($params['page_params']);
		}
		$lastParams=[
			'template_id'=>$state['template_id'],
			'touser'=>$params['openid'],
			'page'=>$page,
			'emphasis_keyword'=>isset($params['emphasis_keyword'])?$params['emphasis_keyword']:''
		];
		$data = null;
		switch ($templateName) {
			case XcxTemplateMessageEnum::MONEY_CHANGE:
				$data = $this->_getMoneyChangeMessageData($params);
				break;
			case XcxTemplateMessageEnum::BUY_SUCCESS:
				$data = $this->_getBuySuccessMessageData($params);
				break;
			case XcxTemplateMessageEnum::SELL_SUCCESS:
				$data = $this->_getSellSuccessMessageData($params);
				break;
			case XcxTemplateMessageEnum::COLLECT_SUCCESS:
				$data = $this->_getCollectSuccessMessageData($params);
				break;
			case XcxTemplateMessageEnum::NEW_CUSTOMER_ACCESS:
				$data = $this->_getNewCustomerAccessMessageData($params);
				break;
			case XcxTemplateMessageEnum::FRIEND_HELP_RESULT:
				$data = $this->_getFriendHelpResultMessageData($params);
				break;
		}
		if(!$data)
			throw new Exception('模板枚举不存在');
		$lastParams['data'] = $data;
		//入队
		MessageQueueBll::getInstance()->push($lastParams);

			
	}
	/**
	 * 余额变动　data
	 * @param  array  $params 必填:type,change_money,change_time,current_money
	 * @return array         
	 * @author binghe 2018-08-08
	 */
	private function _getMoneyChangeMessageData(array $params)
	{
		//类型,变动金额,变动时间,当前余额
		$keys = ['type','change_money','change_time','current_money'];
		if(!valid_keys_exists($keys,$params))
			throw new Exception('模板参数错误');
		$data                  = [
            'keyword1' => ['value' => $params['type']],
            'keyword2' => ['value' => $params['change_money']],
            'keyword3' => ['value' => $params['change_time']],
            'keyword4' => ['value' => $params['current_money']]
        ];
        return $data; 
	}
	/**
	 * 购买成功
	 * @param  array  $params [goods_name,shop_name,expire_time,buy_price,buy_time,trade_no]
	 * 此方法需要添加页面参数　page_params=>[tid]
	 * @return array        
	 * @author binghe 2018-08-09
	 */
	private function _getBuySuccessMessageData(array $params)
	{
		//物品名称,店铺名称,有效期,购买价格,购买时间,交易单号,页面参数(数组)
		$keys = ['goods_name','shop_name','expire_time','buy_price','buy_time','trade_no','page_params'];
		if(!valid_keys_exists($keys,$params))
			throw new Exception('模板参数错误');
			
		$data                  = [
            'keyword1' => ['value' => $params['goods_name']],
            'keyword2' => ['value' => $params['shop_name']],
            'keyword3' => ['value' => $params['expire_time']],
            'keyword4' => ['value' => $params['buy_price']],
            'keyword5' => ['value' => $params['buy_time']],
            'keyword6' => ['value' => $params['trade_no']]
        ];
        return $data; 
	}
	/**
	 * 销售成功
	 * @param  array  $params [trode_no,order_time,goods_name,order_money]
	 * @return array        
	 * @author binghe 2018-08-09
	 */
	private function _getSellSuccessMessageData(array $params)
	{
		//订单号，下单时间，商品名称，订单金额
		$keys = ['trade_no','order_time','goods_name','order_money'];
		if(!valid_keys_exists($keys,$params))
			throw new Exception('模板参数错误');
			
		$data                  = [
            'keyword1' => ['value' => $params['trade_no']],
            'keyword2' => ['value' => $params['order_time']],
            'keyword3' => ['value' => $params['goods_name']],
            'keyword4' => ['value' => $params['order_money']]
        ];
        return $data; 
	}
	/**
	 * 收藏成功
	 * @param  array  $params [shop_name,collect_time,collect_username]
	 * @return array        
	 * @author binghe 2018-08-09
	 */
	private function _getCollectSuccessMessageData(array $params)
	{
		//店铺名称，收藏时间，收藏人昵称
		$keys = ['shop_name','collect_time','collect_username'];
		if(!valid_keys_exists($keys,$params))
			throw new Exception('模板参数错误');
			
		$data                  = [
            'keyword1' => ['value' => $params['shop_name']],
            'keyword2' => ['value' => $params['collect_time']],
            'keyword3' => ['value' => $params['collect_username']]
        ];
        return $data; 
	}
	/**
	 * 新客访问提醒
	 * @param  array  $params ['username','mobile','access_time','source']
	 * @return array        
	 * @author binghe 2018-08-14
	 */
	private function _getNewCustomerAccessMessageData(array $params)
	{
		//昵称,联系方式,访问时间,来源(请写店铺名称)
		$keys = ['username','mobile','access_time','source'];
		if(!valid_keys_exists($keys,$params))
			throw new Exception('模板参数错误');
			
		$data                  = [
            'keyword1' => ['value' => $params['username']],
            'keyword2' => ['value' => $params['mobile']],
            'keyword3' => ['value' => $params['access_time']],
            'keyword4' => ['value' => $params['source']]
        ];
        return $data; 
	}
	/**
	 * 好友助力结果通知
	 * @param  array  $params ['activity_name','result','award','shop_name','tip']
	 * 此方法需要添加页面参数　page_params=>[source_type=>ydym|sq,aid,goods_id]
	 * @return array        
	 * @author binghe 2018-08-14
	 */
	private function _getFriendHelpResultMessageData(array $params)
	{
		//活动名称,助力结果,助力奖励,门店名称,温馨提示
		$keys = ['activity_name','result','award','shop_name','tip'];
		if(!valid_keys_exists($keys,$params))
			throw new Exception('模板参数错误');
			
		$data                  = [
            'keyword1' => ['value' => $params['activity_name']],
            'keyword2' => ['value' => $params['result']],
            'keyword3' => ['value' => $params['award']],
            'keyword4' => ['value' => $params['shop_name']],
            'keyword5' => ['value' => $params['tip']]
        ];
        return $data; 
	}
}