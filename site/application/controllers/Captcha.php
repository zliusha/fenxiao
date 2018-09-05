<?php

/**
 * @Author: binghe
 * @Date:   2018-08-17 17:24:06
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-21 14:24:12
 */
use Service\Bll\Basic\CaptchaBll;
use Gregwar\Captcha\CaptchaBuilder;
/**
 * 图片验证码
 */
class Captcha extends base_Controller
{
	
	public function index()
	{
		$t = $this->input->get('t');
		if(empty($t))
			show_error('无效验证码');
		$phrase = (new CaptchaBll())->getPhrase($t);
		if(!$phrase)
			show_error('验证码已过期');
		ob_clean();
        header("Content-Type: image/jpeg");
        $captcha = new CaptchaBuilder((string)$phrase);
        //#00a0e9 蓝色
        $captcha->setBackgroundColor(0,160,233);
        $captcha->build();
		$captcha->output();
	}
}