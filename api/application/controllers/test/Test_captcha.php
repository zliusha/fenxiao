<?php

/**
 * @Author: binghe
 * @Date:   2018-08-17 14:11:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-17 17:58:31
 */
use Gregwar\Captcha\CaptchaBuilder;

/**
 * 图片验证码
 */
class Test_captcha extends CI_controller
{

    public function index()
    {
    	
    	ob_clean();
        header("Content-Type: image/jpeg");
        $captcha = new CaptchaBuilder;
        $captcha->build();
        
		$captcha->output();
        
    }
    public function index2()
    {
    	echo "<img src ='/test/test_captcha' onclick=\"this.src='/test/test_captcha?n='+Math.random()\" width=100 height=40 style='cursor:pointer' />";
    }
}
