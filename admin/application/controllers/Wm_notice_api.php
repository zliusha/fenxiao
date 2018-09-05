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
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmMainDb;
use Service\DbFrame\DataBase\WmMainDbModels\WmNoticeDao;
class Wm_notice_api extends crud_controller
{
    /**
     * 外卖通知列表
     */
    public function grid_data()
    {
        $title = $this->input->get('title');


        $mainDb = WmMainDb::i();

        $page = new PageList($mainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$mainDb->tables['wm_notice']}";
        $p_conf->order = 'id desc';

        if(!empty($title))
        {
          $p_conf->where .= " AND title LIKE '%".$page->filterLike($title)."%'";
        }

        $count = 0;
        $list = $page->getList($p_conf, $count);

        $data['total'] = $count;
        $data['rows'] = convert_client_list($list);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 删除
     */
    function ids_del()
    {
        $rules = [
            ['field'=>'ids','label'=>'标题','rules'=>'trim|required|preg_key[IDS]']
        ];
        $this->check->check_ajax_form($rules);
        $f_data = $this->form_data($rules);

        $wmNoticeDao = WmNoticeDao::i();

        $ids_arr = explode(',',$f_data['ids']);
        if($wmNoticeDao->inDelete($ids_arr))
        {
            $this->slog('删除记录-'.$this->url_class,$f_data['ids']);
            $this->json_do->out_put();
        }
        else
        {
            $this->json_do->set_msg('删除0条:纪录不存在!');
            $this->json_do->out_put();
        }

    }
  /**
   * 外卖公告添加
   */
    public function add()
    {
        $rules = [
            ['field'=>'title','label'=>'标题','rules'=>'trim|required'],
            ['field'=>'content','label'=>'内容','rules'=>'trim|required', 'xss_clean'=>false],
            ['field'=>'status','label'=>'状态','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $f_data = $this->form_data($rules);


        $wmNoticeDao = WmNoticeDao::i();
        $id = $wmNoticeDao->create($f_data);

        if(!$id)
        {
          $this->json_do->set_error('002', '添加失败');
        }

        $this->json_do->set_msg('添加成功');
        $this->json_do->out_put();
    }

  /**
   * 外卖公告编辑
   */
    public function edit()
    {
        $rules = [
          ['field'=>'id','label'=>'ID','rules'=>'trim|required|numeric'],
          ['field'=>'title','label'=>'标题','rules'=>'trim|required'],
          ['field'=>'content','label'=>'内容','rules'=>'trim|required', 'xss_clean'=>false],
          ['field'=>'status','label'=>'状态','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $f_data = $this->form_data($rules);


        $wmNoticeDao = WmNoticeDao::i();
        $return= $wmNoticeDao->update($f_data, ['id'=>$f_data['id']]);

        if($return === false)
        {
          $this->json_do->set_error('002', '修改失败');
        }

        $this->json_do->set_msg('修改成功');
        $this->json_do->out_put();
    }
    /**
     * 公告详情
     * @param int $id
     */
    public function detail()
    {
        $id = $this->input->post_get('id');
        if(!is_numeric($id))
          $this->json_do->set_error('001');

        $wmNoticeDao = WmNoticeDao::i();
        $m_notice = $wmNoticeDao->getOne(['id'=>$id]);
        $m_notice->time = date('Y-m-d H:i:s', $m_notice->time);

        $data['m_notice'] = $m_notice;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 公告状态更新
     * @param int $id
     */
    public function status_update()
    {
        $id = $this->input->post_get('id');
        $status = $this->input->post_get('status');
        if(!is_numeric($id) || !is_numeric($status))
          $this->json_do->set_error('001');

        $wmNoticeDao = WmNoticeDao::i();
        $return= $wmNoticeDao->update(['status'=>$status, 'pubtime'=>time()], ['id'=>$id]);

        if($return === false)
        {
          $this->json_do->set_error('002', '修改失败');
        }

        $this->json_do->set_msg('修改成功');
        $this->json_do->out_put();
    }
}