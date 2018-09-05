<?php
/**
 * @Author: binghe
 * @Date:   2018-01-23 13:21:43
 * @Last Modified by:   liusha
 * @Last Modified time: 2016-08-24 10:46:54
 */
/**
* 微商城的公告
*/
class Wsc_main_article extends crud_controller
{
      public function index()
      {

          $this->load->view('wsc/index');
      }

      public function add()
      {

          $this->load->view('wsc/add');
      }

      public function edit($id=0)
      {

          $this->load->view('wsc/edit',['id'=>$id]);
      }

      public function detail($id=0)
      {

          $this->load->view('wsc/detail',['id'=>$id]);
      }
}