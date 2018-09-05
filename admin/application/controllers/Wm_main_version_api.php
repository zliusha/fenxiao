<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/23
 * Time: 17:35
 */
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\MainDb;
use Service\DbFrame\DataBase\MainDbModels\MainVersionDao;

class Wm_main_version_api extends crud_controller
{
    /**
     * 列表数据
     */
    public function grid_data()
    {

        $mainDb = MainDb::i();

        $page = new PageList($mainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$mainDb->tables['main_version']}";
        $p_conf->order = 'id desc';


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

        $MainVersionDao = MainVersionDao::i();

        $ids_arr = explode(',',$f_data['ids']);
        if($MainVersionDao->inDelete($ids_arr))
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
     * 添加
     */
    public function add()
    {
        $rules = [
            ['field'=>'device_type','label'=>'设备类型','rules'=>'trim|required|numeric'],
            ['field'=>'type','label'=>'类型','rules'=>'trim|required|numeric'],
            ['field'=>'version','label'=>'版本号','rules'=>'trim|required|preg_key[VERSION]'],
            ['field'=>'remark','label'=>'版本描述','rules'=>'trim|required'],
            ['field'=>'download_url','label'=>'下载链接','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $f_data = $this->form_data($rules);
        $f_data['v_int'] = str_replace(".", "", $f_data['version']);

        $MainVersionDao = MainVersionDao::i();
        $id = $MainVersionDao->create($f_data);

        if(!$id)
        {
            $this->json_do->set_error('002', '添加失败');
        }

        $this->json_do->set_msg('添加成功');
        $this->json_do->out_put();
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $rules = [
            ['field'=>'id','label'=>'ID','rules'=>'trim|required|numeric'],
            ['field'=>'device_type','label'=>'设备类型','rules'=>'trim|required|numeric'],
            ['field'=>'type','label'=>'类型','rules'=>'trim|required|numeric'],
            ['field'=>'version','label'=>'版本号','rules'=>'trim|required|preg_key[VERSION]'],
            ['field'=>'remark','label'=>'版本描述','rules'=>'trim|required'],
            ['field'=>'download_url','label'=>'下载链接','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $f_data = $this->form_data($rules);
        $f_data['v_int'] = str_replace(".", "", $f_data['version']);

        $MainVersionDao = MainVersionDao::i();
        $return= $MainVersionDao->update($f_data, ['id'=>$f_data['id']]);

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

        $MainVersionDao = MainVersionDao::i();
        $m_main_version = $MainVersionDao->getOne(['id'=>$id]);
        $m_main_version->time = date('Y-m-d H:i:s', $m_main_version->time);

        $data['main_version_info'] = $m_main_version;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 状态更新
     * @param int $id
     */
    public function status_update()
    {
        $id = $this->input->post_get('id');
        $status = $this->input->post_get('status');
        if(!is_numeric($id) || !is_numeric($status))
            $this->json_do->set_error('001');

        $MainVersionDao = MainVersionDao::i();
        $return= $MainVersionDao->update(['status'=>$status], ['id'=>$id]);

        if($return === false)
        {
            $this->json_do->set_error('002', '修改失败');
        }

        $this->json_do->set_msg('修改成功');
        $this->json_do->out_put();
    }
}
