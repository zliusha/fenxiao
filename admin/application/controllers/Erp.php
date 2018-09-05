<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/1/29
 * Time: 13:57
 */
class Erp extends crud_controller
{
      public function index()
      {

          $this->load->view('erp/index');
      }
}