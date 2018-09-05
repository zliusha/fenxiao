<?php
use Service\Cache\WmPrintConfigCache;
/**
 * @Author: binghe
 * @Date:   2017-10-30 18:36:52
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 13:27:22
 */
/**
* 打印机api
*/
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmPrintDao;
class Prints_api extends wm_service_controller
{
    /**
     * 打印机分页列表
     * @return [type] [description]
     */
    public function list()
    {
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$wmShardDb->tables['wm_print']}";
        $p_conf->where = " aid={$this->s_user->aid}";
        $p_conf->order = 'id desc';

        if(!$this->is_zongbu)
            $p_conf->where.=" AND shop_id={$this->currentShopId} ";
        $count = 0;
        $list = $page->getList($p_conf, $count);

        $c_rules = array(
          array('type' => 'time', 'field' => 'time', 'format'=>'Y-m-d H:i:s')
        );
        $list = convert_client_list($list, $c_rules);

        $data['total'] = $count;
        $data['rows'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 添加打印机
     */
    public function add()
    {
        $rules = [
          ['field' => 'print_name', 'label' => '设备名称', 'rules' => 'trim|required'],
          ['field' => 'print_deviceno', 'label' => '设备号码', 'rules' => 'trim|required'],
          ['field' => 'print_key', 'label' => '设备秘钥', 'rules' => 'trim|required'],
          ['field' => 'times', 'label' => '打印联数', 'rules' => 'trim|required|in_list[1,2]']
        ];

        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        if($this->is_zongbu)
            $where['shop_id']=0;
        else
            $where['shop_id']=$this->currentShopId;

        $wmPrintDao = WmPrintDao::i($this->s_user->aid);
        $m_wm_print = $wmPrintDao->getOne(['aid'=>$this->s_user->aid,'shop_id'=>$where['shop_id']]);
        if($m_wm_print)
            $this->json_do->set_error('004','打印机已存在');

        $fdata['shop_id']=$where['shop_id'];
        $fdata['aid'] = $this->s_user->aid;

        if($wmPrintDao->create($fdata))
        {
            $this->json_do->set_msg('添加成功');
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005','添加失败');
    }
    /**
     * 删除
     */
    public function del()
    {
        
        $rules = [
          ['field' => 'id', 'label' => 'id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmPrintDao = WmPrintDao::i($this->s_user->aid);
        if($wmPrintDao->delete(['id'=>$fdata['id'],'aid'=>$this->s_user->aid]))
        {
            $this->json_do->set_msg('删除成功');
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005','删除打印机失败');
    }
    /**
     * 编辑
     * @return [type] [description]
     */
    public function edit()
    {

        $rules = [
          ['field' =>'id','label' => '打印机id','rules'=>'trim|required|numeric'],
          ['field' => 'print_name', 'label' => '设备名称', 'rules' => 'trim|required'],
          ['field' => 'print_deviceno', 'label' => '设备号码', 'rules' => 'trim|required'],
          ['field' => 'print_key', 'label' => '设备秘钥', 'rules' => 'trim|required'],
          ['field' => 'times', 'label' => '打印联数', 'rules' => 'trim|required|in_list[1,2]']
        ];

        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmPrintDao = WmPrintDao::i($this->s_user->aid);
        $m_wm_print = $wmPrintDao->getOne(['id'=>$fdata['id'],'aid'=>$this->s_user->aid]);
        if(!$m_wm_print)
            $this->json_do->set_error('004','打印机不存在');

        $where['id']=$m_wm_print->id;
        unset($fdata['id']);
        if($wmPrintDao->update($fdata,$where)!==false)
        {
            //删除打印机缓存
            (new WmPrintConfigCache(['aid'=>$this->s_user->aid,'shop_id'=>$this->currentShopId]))->delete();

            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        }
        else
        {
            $this->json_do->set_error('005','修改打印机信息失败');
        }
    }
    /**
     * 详情
     * @return [type] [description]
     */
    public function info()
    {
        $print_id = $this->input->get('print_id');
        if(empty($print_id) || !is_numeric($print_id))
            $this->json_do->set_error('001');

        $wmPrintDao = WmPrintDao::i($this->s_user->aid);
        $model = $wmPrintDao->getOne(['id'=>$print_id,'aid'=>$this->s_user->aid]);
        if($model)
        {
            $data['info']=$model;
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('004','打印机不存在');
    }
    /*****以上方法过期******/
    /**
     * 打印机信息
     * @return [type] [description]
     */
    public function info2()
    {
        $wmPrintDao = WmPrintDao::i($this->s_user->aid);
        //总店可配置为0
        $shop_id = 0;
        if(!$this->is_zongbu)
            $shop_id = $this->currentShopId;
        $model = $wmPrintDao->getOne(['aid'=>$this->s_user->aid,'shop_id'=>$shop_id]);
        $data['info'] = $model;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 保存打印机配置
     * @return [type] [description]
     */
    public function save()
    {
        $rules = [
          ['field' => 'type', 'label' => '打印机类型', 'rules' => 'trim|required|in_list[1,2]'],
          ['field' => 'print_name', 'label' => '设备名称', 'rules' => 'trim'],
          ['field' => 'print_deviceno', 'label' => '设备号码', 'rules' => 'trim'],
          ['field' => 'print_key', 'label' => '设备秘钥', 'rules' => 'trim'],
          ['field' => 'times', 'label' => '打印联数', 'rules' => 'trim|required|in_list[1,2]']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        //type 1 365打印机,2 usb打印机 选择365时,必须填写设置信息
        if($fdata['type'] == 1)
        {
            if(empty($fdata['print_name']) || empty($fdata['print_deviceno']) || empty($fdata['print_key']))
                $this->json_do->set_error('001','365WIFI打印机必需填写设备信息');
        }
        else    //保留信息
        {
            unset($fdata['print_name']);
            unset($fdata['print_deviceno']);
            unset($fdata['print_key']);
        }


        $shop_id = 0;
        if(!$this->is_zongbu)
            $shop_id = $this->currentShopId;

        $wmPrintDao = WmPrintDao::i($this->s_user->aid);
        $model = $wmPrintDao->getOne(['aid'=>$this->s_user->aid,'shop_id'=>$shop_id]);
        if($model)
        {
            //更新打印机
            $wmPrintDao->update($fdata,['aid'=>$this->s_user->aid,'id'=>$model->id]);
            //删除打印机缓存
            (new WmPrintConfigCache(['aid'=>$this->s_user->aid,'shop_id'=>$shop_id]))->delete();

            $this->json_do->out_put();
        }
        else
        {
            $fdata['aid'] = $this->s_user->aid;
            $fdata['shop_id'] = $this->currentShopId;
            $wmPrintDao->create($fdata);
            $this->json_do->out_put();
        }
    }
}