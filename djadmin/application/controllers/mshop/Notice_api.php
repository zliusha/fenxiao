<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2017/11/28
 * Time: 17:44
 */
use Service\DbFrame\DataBase\WmMainDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmMainDbModels\WmNoticeDao;
class Notice_api extends wm_service_controller
{
  /**
   * 公告列表
   */
    public function get_list()
    {
        $mainDb = WmMainDb::i();
        $page = new PageList($mainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$mainDb->tables['wm_notice']}";
        $p_conf->order = 'id desc';
        $p_conf->where .= ' AND status=1';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        $c_rule = [
          ['type'=>'time','field'=>'time', 'format'=>'Y-m-d']
        ];
        $data['total'] = $count;
        $data['rows'] = convert_client_list($list, $c_rule);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

  /**
   * 公告详情
   * @param int $id
   */
    public function get_info($id=0)
    {
        if(!is_numeric($id))
            $this->json_do->set_error('001');

        $wmNoticeDao = WmNoticeDao::i();
        $m_notice = $wmNoticeDao->getOne(['id'=>$id]);

        $data['m_notice'] = $m_notice;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

}