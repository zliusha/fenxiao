<?php
/**
 * @Author: liusha
 * @Date:   2017-12-12 10:02:21
 * @Last Modified by:   liusha
 * @Last Modified time: 2017-12-18 17:11:12
 */
/**
 * app_controller
 */
class site_controller extends base_controller
{
  public $s_user = null;
  public function __construct()
  {
      parent::__construct();

      //验证请求域名
      $this->_valid_origin();
  }

  private function _valid_origin()
  {
      if(!$_SERVER['HTTP_ORIGIN'])
      {
          exit('禁止访问');
      }
      $site_auth = &inc_config('site_auth');

      $origin = $_SERVER['HTTP_ORIGIN'];
      $domain = str_replace(URL_SCHEME, '', $origin);

      if(in_array(ENVIRONMENT, ['production']) && !in_array($domain, $site_auth['origin_white']))
      {
          exit('禁止访问2');
      }
  }

}