<?php

/**
 * @Author: binghe
 * @Date:   2018-08-07 16:44:14
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-09 16:19:20
 */
namespace Service\Bll\Hcity\Xcx;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityXcxFormidDao;
use Service\Support\FLock;
use Service\Exceptions\Exception;
/**
 * 小程序formId
 */
class XcxFormIdBll extends \Service\Bll\BaseBll
{
	/**
	 * 添加formId
	 * @param array       $params 必填：form_id,open_id
	 * @param int|integer $type   0formId,1prepay_id
	 */
	public function add(array $params,int $type = 0)
	{
		//由于微信违规会导致模板被封，此处加锁防止一条formId多次插入,造成一条formId多次下发导致的违规
		if (!FLock::getInstance()->lock('Xcx_XcxFormIdBll:add:' . $params['open_id'])) {
            throw new Exception('过于频繁,请稍后再试!');
        }
		$hcityXcxFormidDao = HcityXcxFormidDao::i();
		$one = $hcityXcxFormidDao->getOne(['form_id'=>$params['form_id']]);
		if($one)
			throw new Exception('记录已存在');
			
		$params = [
			'form_id'=>$params['form_id'],
			'open_id'=>$params['open_id'],
			'msg_count'=>$type == 1 ? 3 : 1,
			'type' => $type
		];
		return HcityXcxFormidDao::i()->create($params);
	}
	/**
	 * 获取formId
	 * @param  string $openId [description]
	 * @return null or $formId
	 */
	public function get(string $openId)
	{
		//由于微信违规会导致模板被封，此处加锁防止重复取
		if (!FLock::getInstance()->lock('Xcx_XcxFormIdBll:get:' . $openId)) {
            throw new Exception('过于频繁,请稍后再试!');
        }
        //formId 7 天有效
        $time = time() - 3600 * 24 * 7 + 300;
        $hcityXcxFormidDao = HcityXcxFormidDao::i();
		$one = $hcityXcxFormidDao->getOne(['open_id'=>$openId,'msg_count >'=>0,'time >'=>$time],'id,msg_count,form_id','time asc');
		if(!$one)
			return null;
		//计数减1
		$hcityXcxFormidDao->update(['msg_count'=>$one->msg_count - 1],['id'=>$one->id]);
		return $one->form_id;
	}

}