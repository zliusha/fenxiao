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
class Wsc_main_article_api extends crud_controller
{


//    /**
//     * 外卖通知列表
//     */
//    public function grid_data()
//    {
//        $title = $this->input->get('title');
//        $cate = $this->input->post_get('cate');
//
//        //设置微商城链接
//        $hosts = &inc_config('hosts');
//        set_to_vars('host', $hosts['wsc_host']);
//
//        $page = new page_list();
//        $p_conf = $page->get_m_config();
//        $p_conf->table = "dbp_main_article";
//        $p_conf->order = 'id desc';
//
//        if(!empty($title))
//        {
//          $p_conf->where .= " AND title LIKE '%".$page->filter_like($title)."%'";
//        }
//        if(is_numeric($cate) && $cate > 0)
//        {
//          $p_conf->where .= " AND cate={$cate}";
//        }
//
//        $count = 0;
//        $list = $page->get_list($p_conf, $count);
//
//        // 数据二次处理格式
//        $v_rules = [
//          ['type' => 'time', 'field' => 'time'],
//          ['type' => 'time', 'field' => 'pubtime']
//        ];
//        $data['total'] = $count;
//        $data['rows'] = convert_client_list($list, $v_rules);
//        $this->json_do->set_data($data);
//        $this->json_do->out_put();
//    }
//
//    public function add()
//    {
//        $rules = [
//            ['field'=>'cate','label'=>'分类','rules'=>'trim|required|numeric'],
//            ['field'=>'title','label'=>'标题','rules'=>'trim|required'],
//            ['field'=>'content','label'=>'内容','rules'=>'trim|required', 'xss_clean'=>false],
//            ['field'=>'state','label'=>'状态','rules'=>'trim|required|numeric'],
//            ['field'=>'is_index','label'=>'是否商城概况展示','rules'=>'trim|required|numeric']
//        ];
//        $this->check->check_ajax_form($rules);
//        $f_data = $this->form_data($rules);
//
//
//        $wsc_main_article_dao = new wsc_main_article_dao();
//        $id = $wsc_main_article_dao->create($f_data);
//
//        if(!$id)
//        {
//          $this->json_do->set_error('002', '添加失败');
//        }
//
//        $this->json_do->set_msg('添加成功');
//        $this->json_do->out_put();
//    }
//
//    public function edit()
//    {
//        $rules = [
//            ['field'=>'id','label'=>'ID','rules'=>'trim|required|numeric'],
//            ['field'=>'cate','label'=>'分类','rules'=>'trim|required|numeric'],
//            ['field'=>'title','label'=>'标题','rules'=>'trim|required'],
//            ['field'=>'content','label'=>'内容','rules'=>'trim|required', 'xss_clean'=>false],
//            ['field'=>'state','label'=>'状态','rules'=>'trim|required|numeric'],
//            ['field'=>'is_index','label'=>'是否商城概况展示','rules'=>'trim|required|numeric']
//        ];
//        $this->check->check_ajax_form($rules);
//        $f_data = $this->form_data($rules);
//
//
//        $wsc_main_article_dao = new wsc_main_article_dao();
//        $return= $wsc_main_article_dao->update($f_data, ['id'=>$f_data['id']]);
//
//        if($return === false)
//        {
//          $this->json_do->set_error('002', '修改失败');
//        }
//
//        $this->json_do->set_msg('修改成功');
//        $this->json_do->out_put();
//    }
//
//    /**
//     * 文章详情
//     */
//    public function detail()
//    {
//        $id = (int) $this->input->post_get('id');
//
//        $main_article_dao = new wsc_main_article_dao();
//        $content = $main_article_dao->get_one_array(['id' => $id]);
//
//        $content['time'] = date('Y-m-d H:i:s', $content['time']);
//        $content['pubtime'] = date('Y-m-d H:i:s', $content['pubtime']);
//
//        $this->json_do->set_data($content);
//        $this->json_do->out_put();
//    }
//
//    /**
//     * 公告状态更新
//     * @param int $id
//     */
//    public function status_update()
//    {
//      $id = $this->input->post_get('id');
//      $status = $this->input->post_get('status');
//      if(!is_numeric($id) || !is_numeric($status))
//        $this->json_do->set_error('001');
//
//      $main_article_dao = new wsc_main_article_dao();
//      $return= $main_article_dao->update(['is_index'=>$status, 'pubtime'=>time()], ['id'=>$id]);
//      log_message('error', $main_article_dao->db->last_query());
//      if($return === false)
//      {
//        $this->json_do->set_error('002', '修改失败');
//      }
//
//      $this->json_do->set_msg('修改成功');
//      $this->json_do->out_put();
//    }
}