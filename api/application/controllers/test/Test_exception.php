<?php

/**
 * @Author: binghe
 * @Date:   2018-06-21 10:21:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-06 11:15:55
 */
use Service\Exceptions\CacheException;
/**
 * test
 */
class Test_exception extends base_controller
{
	
	public function index()
	{
		throw new RuntimeException("Error Processing Request", 1);
		echo 111;
	}
	public function error()
	{
		throw new CacheException();
		
	}
}