<?php
/**
 * @Author: binghe
 * @Date:   2018-01-23 13:21:43
 * @Last Modified by:   liusha
 * @Last Modified time: 2016-08-24 10:46:54
 */
/**
* 首页
*/
class Home extends crud_controller
{
      public function index()
      {

          $this->load->view('home/index');
      }
}