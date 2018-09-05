<?php

/**
 * @Author: binghe
 * @Date:   2018-06-14 15:33:35
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 15:51:30
 */
use Service\Cache\WmFubeiConfigCache;
/**
 * 付呗
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmFubeiConfigDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmFubeiRefshopDao;
class Fubei_config_api extends wm_service_controller
{
	
	/*
    添加门店配置 编辑与配置
     */
    public function shop_edit()
    {
        $rules=[
            ['field'=>'shop_id','label'=>'门店id','rules'=>'trim|required|numeric'],
            ['field'=>'fubei_id','label'=>'银行通道id','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $m_wm_shop = $wmShopDao->getOne(['aid'=>$this->s_user->aid,'id'=>$fdata['shop_id']]);
        if(!$m_wm_shop)
        	$this->json_do->set_error('004','门店不存在');

        $wmFubeiConfigDao = WmFubeiConfigDao::i($this->s_user->aid);
        $m_wm_fubei_config = $wmFubeiConfigDao->getOne(['aid'=>$this->s_user->aid,'id'=>$fdata['fubei_id']]);
        if(!$m_wm_fubei_config)
        	$this->json_do->set_error('004','银行通道不存在');

        $wmFubeiRefshopDao = WmFubeiRefshopDao::i($this->s_user->aid);
        $m_wm_fubei_refshop = $wmFubeiRefshopDao->getOne(['aid'=>$this->s_user->aid,'shop_id'=>$fdata['shop_id']]);
        $fdata['update_time'] = time();
        if($m_wm_fubei_refshop)
        {
            if($wmFubeiRefshopDao->update($fdata,['id'=>$m_wm_fubei_refshop->id])!==false)
            {
                $this->json_do->set_msg('保存成功');
                $this->json_do->out_put();
            }
            else
                $this->json_do->set_error('005','保存失败');
        }
        else
        {
            $fdata['aid']=$this->s_user->aid;
            if($wmFubeiRefshopDao->create($fdata))
            {
                $this->json_do->set_msg('保存成功');
                $this->json_do->out_put();
            }
            else
                $this->json_do->set_error('005','保存失败');
        }
    }
	
	/**
     * 配置的银行通道门店列表
     * @return [type] [description]
     */
    public function shop_list()
    {
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->fields= 'a.id as shop_id,a.shop_name,b.id,b.fubei_id,b.time,c.name';
        $p_conf->table = "{$wmShardDb->tables['wm_shop']} a left join {$wmShardDb->tables['wm_fubei_refshop']} b on a.id=b.shop_id  left join {$wmShardDb->tables['wm_fubei_config']} c on b.fubei_id = c.id";
        $p_conf->where = " a.aid={$this->s_user->aid} and a.is_delete = 0 ";
        $p_conf->order = 'a.time desc';

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
     * 添加银行通道
     */
	public function add()
	{
		$rules=[
            ['field'=>'name','label'=>'通道名称','rules'=>'trim|required'],
            ['field'=>'app_id','label'=>'商户平台ID','rules'=>'trim|required'],
            ['field'=>'app_secret','label'=>'商户平台Secret','rules'=>'trim|required'],
            ['field'=>'store_id','label'=>'商户门店ID','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $fdata['aid'] = $this->s_user->aid;
        $wmFubeiConfigDao = WmFubeiConfigDao::i($this->s_user->aid);
        if($wmFubeiConfigDao->create($fdata))
        {
        	$this->json_do->set_msg('添加成功');
            $this->json_do->out_put();
        }
        else
        	$this->json_do->set_error('005','添加失败');
	}
	/**
	 * 编辑银行通道
	 * @return [type] [description]
	 */
	public function edit()
	{
		$rules=[
			['field'=>'id','label'=>'id','rules'=>'trim|required|numeric'],
            ['field'=>'name','label'=>'通道名称','rules'=>'trim|required'],
            ['field'=>'app_id','label'=>'商户平台ID','rules'=>'trim|required'],
            ['field'=>'app_secret','label'=>'商户平台Secret','rules'=>'trim|required'],
            ['field'=>'store_id','label'=>'商户门店ID','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $id=$fdata['id'];
        unset($fdata['id']);
        $wmFubeiConfigDao = WmFubeiConfigDao::i($this->s_user->aid);
        if($wmFubeiConfigDao->update($fdata,['id'=>$id,'aid'=>$this->s_user->aid])!==false)
        {
        	$this->_cleanCache($this->s_user->aid,$id);
        	$this->json_do->set_msg('编辑成功');
            $this->json_do->out_put();
        }
        else
        	$this->json_do->set_error('005','编辑失败');
	}
	/**
	 * 银行通道列表
	 * @return [type] [description]
	 */
	public function list()
	{
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->fields= '*';
        $p_conf->table = "{$wmShardDb->tables['wm_fubei_config']}";
        $p_conf->where = " aid={$this->s_user->aid}";
        $p_conf->order = 'time asc';

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
	 * 删除银行通道
	 * @return [type] [description]
	 */
	public function delete()
	{
		$rules=[
			['field'=>'id','label'=>'id','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $where['id'] = $fdata['id'];
        $where['aid'] = $this->s_user->aid;
        $wmFubeiConfigDao = WmFubeiConfigDao::i($this->s_user->aid);
        if($wmFubeiConfigDao->delete($where))
        {
        	$this->_cleanCache($where['aid'],$where['id']);
        	$this->json_do->set_msg('删除成功');
            $this->json_do->out_put();
        }
        else
        	$this->json_do->set_error('005','删除失败');
	}
	/**
	 * 清除通道缓存
	 * @return [type] [description]
	 */
	private function _cleanCache($aid,$fubei_id)
	{
		//编辑时,删除配置缓存
        return (new WmFubeiConfigCache(['aid'=>$aid,'fubei_id'=>$fubei_id]))->delete();
	}
}