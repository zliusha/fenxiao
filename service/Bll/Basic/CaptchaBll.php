<?php

/**
 * @Author: binghe
 * @Date:   2018-08-17 17:05:54
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-17 17:19:21
 */
namespace Service\Bll\Basic;
use Service\Cache\Basic\CaptchaCache;
/**
 * 验证码
 */
class CaptchaBll extends \Service\Bll\BaseBll
{
	/**
	 * 获取验证码
	 * @param  string $token 验证码token
	 * @return string        
	 * @author binghe 2018-08-17
	 */
	public function getPhrase(string $token)
	{
		$captchaCache = new CaptchaCache(['token'=>$token]);
		return $captchaCache->get();
	}
	/**
	 * 获取token
	 * @return string md5(guid)
	 * @author binghe 2018-08-17
	 */
	public function getToken()
	{
		$token = md5(create_guid());
		$captchaCache = new CaptchaCache(['token'=>$token]);
		$captcha = rand(1000,9999);
		//验证码5分钟有效
		$captchaCache->save($captcha,300);
		return $token;
	}
	/**
	 * 断言输入的验证码是否正确
	 * @param  string $token 
	 * @param  string $captcha  输入的验证码
	 * @return bool        
	 * @author binghe 2018-08-17
	 */
	public function assertEqual(string $token,string $captcha)
	{
		$data = $this->getPhrase($token);
		if(empty($data))
			return false;
		return $data == $captcha;
	}
}