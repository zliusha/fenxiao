<?php
/**
 * @Author: binghe
 * @Date:   2018-04-04 11:01:14
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-06 11:10:21
 */
namespace Service\Exceptions;
/**
* 
*/
class CacheException extends Exception
{
    protected $code = 1000;
    protected $message = '缓存出错';
   
}