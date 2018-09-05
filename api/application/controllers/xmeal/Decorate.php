<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/4
 * Time: 17:43
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmZxSelectModuleDao;
class Decorate extends xmeal_table_controller
{
    /**
     * 得到单一具体模块数据
     * @return [type] [description]
     */
    public function get_select_module_data()
    {

        $rules = [
            ['field' => 'module_id', 'label' => '模块ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $f_data=$this->form_data($rules);

        $id = $f_data['module_id'];
        $shop_id = $this->table->shop_id;


        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->aid);
        $m_wm_select_module = $wmZxSelectModuleDao->getOne(['module_id' => $id, 'aid' => $this->aid, 'shop_id' => $shop_id]);

        if ($m_wm_select_module && $m_wm_select_module->module_data) {
            $wm_zx_bll = new wm_zx_bll();
            //模块数据
            $m_wm_select_module->module_data = $wm_zx_bll->get_module_do($m_wm_select_module->module_id, unserialize($m_wm_select_module->module_data));

            //模块系统数据
            $m_wm_select_module->sys_data = $wm_zx_bll->get_sys_data($m_wm_select_module);
        }

        $data['module'] = $m_wm_select_module;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}