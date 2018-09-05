<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/2/28
 * Time: 17:30
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmZxSelectModuleDao;
class Decorate extends xcx_controller
{

  /**
   * 获取装修模块数据
   */
  public function get_modules_data()
  {
      $shop_id = $this->input->post_get('shop_id');
      $wm_zx_bll = new wm_zx_bll();
      $m_poster_modules = $wm_zx_bll->get_modules($shop_id, 1, $this->aid);

      $_poster_modules = [];
      //处理时间筛选
      if($m_poster_modules)
      {

          foreach($m_poster_modules as $key => $poster_modules)
          {
              $time = time();
              $start_time = strtotime($poster_modules->module_data->start_day);
              $end_time = strtotime($poster_modules->module_data->end_day)  + 3600*24;

              //不在最大时间范围内，踢出
              if( !($time >= $start_time && $time <= $end_time))
              {
                unset($m_poster_modules[$key]);
                continue;
              }

              $c_week = date('w');
              $weeks = $poster_modules->module_data->week;

              //不在当周几内，踢出
              if(strpos($weeks, $c_week) === false)
              {
                unset($m_poster_modules[$key]);
                continue;
              }

              $is_curr_time_section = is_curr_time_section($poster_modules->module_data->start_time, $poster_modules->module_data->end_time);
              if(!$is_curr_time_section)
              {
                unset($m_poster_modules[$key]);
                continue;
              }

              $_poster_modules[] = $poster_modules;
          }
      }
      $m_tj_goods_modules = $wm_zx_bll->get_modules($shop_id, 2, $this->aid, $this->s_user->uid);

      $data['poster_modules'] = $_poster_modules;
      $data['tj_goods_modules'] = $m_tj_goods_modules;

      $this->json_do->set_data($data);
      $this->json_do->out_put();
  }

  /**
   * 得到单一具体模块数据
   * @return [type] [description]
   */
  public function get_module_data()
  {
      $id = $this->input->post_get('id');
      $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->aid);
      $m_wm_select_module = $wmZxSelectModuleDao->getOne(['id' => $id, 'aid' => $this->aid]);
      if (!$m_wm_select_module)
      {
        $this->json_do->set_error('004', '记录不存在');
      }


      if ($m_wm_select_module->module_data) {
          $wm_zx_bll = new wm_zx_bll();
          //模块数据
          $m_wm_select_module->module_data = $wm_zx_bll->get_module_do($m_wm_select_module->module_id, unserialize($m_wm_select_module->module_data));

          //模块系统数据
          $m_wm_select_module->sys_data = $wm_zx_bll->get_sys_data($m_wm_select_module, $this->s_user->uid);
      }

      $this->json_do->set_data($m_wm_select_module);
      $this->json_do->out_put();
  }

  /**
   * 获取小程序启动页装修数据
   */
  public function get_xcx_index_data()
  {
      $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->aid);
      $m_wm_select_module = $wmZxSelectModuleDao->getOne(['module_id' => 3, 'aid' => $this->aid, 'shop_id' => 0, 'is_save' => 1]);
      if (!$m_wm_select_module)
      {
          $m_wm_select_module = null;
      }


      if (isset($m_wm_select_module->module_data) && !empty($m_wm_select_module->module_data)) {
          $wm_zx_bll = new wm_zx_bll();
          //模块数据
          $m_wm_select_module->module_data = $wm_zx_bll->get_module_do($m_wm_select_module->module_id, @unserialize($m_wm_select_module->module_data));
          if(!$m_wm_select_module->module_data->is_on)
          {
              //临时处理
              $m_wm_select_module->module_data->img = 'http://ozt666mgn.bkt.clouddn.com/wsc_goods/1523436244932_3454.png';
          }

      }
      //为空 填充默认数据
      if(!$m_wm_select_module)
      {
          $m_wm_select_module = new stdClass();
          $m_wm_select_module->module_data = null;
          $wm_zx_bll = new wm_zx_bll();
          //模块数据
          $m_wm_select_module->module_data = $wm_zx_bll->get_module_do(3, null);
          $m_wm_select_module->module_data->img = 'http://ozt666mgn.bkt.clouddn.com/wsc_goods/1523436244932_3454.png';
          $m_wm_select_module->module_data->title = '欢迎页';

      }

      $wmUserDao = WmUserDao::i($this->aid);
      $user_list = $wmUserDao->getAllArray(['aid' => $this->aid], "aid,username,img", "id desc", 10);
      if(empty($user_list))
      {
          //临时处理
          $user = [
              'aid' => $this->aid,
              'username' => '寒夜',
              'img' => 'http://oydp172vs.bkt.clouddn.com/comment_pic/1527222212914_5677.png'
          ];
          $user_list[] = $user;
      }


      $this->json_do->set_data(['module_data' => $m_wm_select_module, 'user_list' => $user_list]);
      $this->json_do->out_put();
  }
}