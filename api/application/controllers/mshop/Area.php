<?php
/**
 * @Author: binghe
 * @Date:   2017-08-09 19:28:02
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-02 15:52:15
 */
/**
 * 获得区域数据
 */
use Service\DbFrame\DataBase\MainDbModels\MainAreaDao;
class Area extends mshop_controller
{
    /**
     *  得到区域，默认得到省级
     * @param  [type] $pid [description]
     * @return [type]      [description]
     */
    public function get_areas($pid = null)
    {
        $rule = [
            ['field' => 'pid', 'label' => 'PID', 'rules' => 'trim|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $pid = $f_data['pid'];
        if ($pid) {
            if (!is_numeric($pid) || strlen($pid) != 6) {
                $this->json_do->set_error('001');
            }
        }
        $mainAreaDao = MainAreaDao::i();
        if (empty($pid)) {
            $where['level'] = 1;
        } else {
            $where['pid'] = $pid;
        }

        $m_main_area_arr = $mainAreaDao->getAllArray($where);
        $data['items'] = $m_main_area_arr;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 得到所有省市区
     * @return [type] [description]
     */
    public function get_all()
    {
        $mainAreaDao = MainAreaDao::i();
        $m_main_area_arr = $mainAreaDao->getAllArray('1=1');
        $data['items'] = $m_main_area_arr;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
