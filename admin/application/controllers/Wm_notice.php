<?php
/**
 * @Author: binghe
 * @Date:   2018-01-23 13:21:43
 * @Last Modified by:   liusha
 * @Last Modified time: 2016-08-24 10:46:54
 */
/**
* 微外卖的公告
*/
class Wm_notice extends crud_controller
{
      public function index()
      {

          $this->load->view('ydb/index');
      }

      public function add()
      {

          $this->load->view('ydb/add');
      }

      public function edit($id=0)
      {

          $this->load->view('ydb/edit',['id'=>$id]);
      }

      public function detail($id=0)
      {

          $this->load->view('ydb/detail',['id'=>$id]);
      }
}