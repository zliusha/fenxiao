<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/2/28
 * Time: 15:10
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmZxSelectModuleDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
class Decorate extends mshop_controller
{

  /**
   * 获取装修模块数据
   */
   public function get_modules_data()
   {
       $rule = [
           ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric']
       ];

       $this->check->check_ajax_form($rule);
       $f_data = $this->form_data($rule);

       $shop_id = $f_data['shop_id'];
       $uid = isset($this->s_user->uid) ? $this->s_user->uid : 0;

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
              $end_time = strtotime($poster_modules->module_data->end_day) + 3600*24;

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
       $m_tj_goods_modules = $wm_zx_bll->get_modules($shop_id, 2, $this->aid, $uid);

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
        $rule = [
            ['field' => 'id', 'label' => 'ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $id = $f_data['id'];
        $uid = isset($this->s_user->uid) ? $this->s_user->uid : 0;

        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->aid);
        $m_wm_select_module = $wmZxSelectModuleDao->getOne(['id' => $id, 'aid' => $this->aid]);
        if (!$m_wm_select_module)
        {
          $this->json_do->set_error('004', '记录不存在');
        }
        $wmShopDao = WmShopDao::i($this->aid);
        $shop_model = $wmShopDao->getOne(['aid' => $this->aid, 'id' => $m_wm_select_module->shop_id, 'is_delete' => 0], 'id,aid,on_time,is_delete,is_newbie_coupon,newbie_coupon,status,freight_money,send_money,use_flow,flow_free_money,service_radius');

        if ($m_wm_select_module->module_data) {
          $wm_zx_bll = new wm_zx_bll();
          //模块数据
          $m_wm_select_module->module_data = $wm_zx_bll->get_module_do($m_wm_select_module->module_id, unserialize($m_wm_select_module->module_data));

          //模块系统数据
          $m_wm_select_module->sys_data = $wm_zx_bll->get_sys_data($m_wm_select_module, $uid);
        }

        $this->json_do->set_data(['shop_model' => $shop_model, 'module_data' => $m_wm_select_module]);
        $this->json_do->out_put();
    }
}