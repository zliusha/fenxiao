<?php
/**
 * @Author: binghe
 * @Date:   2017-11-06 15:42:33
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 15:51:30
 */
use Service\Cache\WmDadaCompanyCache;
use Service\Cache\WmDadaShopCache;

use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmDadaCompanyDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDadaShopDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmDadaCityDao;
/**
* 达达
*/
class dada_api extends wm_service_controller
{
    /**
     * 商户信息
     * @return [type] [description]
     */
    public function info()
    {
        $wmDadaCompanyDao = WmDadaCompanyDao::i($this->s_user->aid);
        $m_wm_dada_company = $wmDadaCompanyDao->getOne(['aid'=>$this->s_user->aid]);
        $data['info']=$m_wm_dada_company;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 商户信息编辑
     * @return [type] [description]
     */
    public function edit()
    {
        $rules=[
            ['field'=>'source_id','label'=>'商户编号','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $wmDadaCompanyDao = WmDadaCompanyDao::i($this->s_user->aid);
        $m_wm_dada_company = $wmDadaCompanyDao->getOne(['aid'=>$this->s_user->aid]);
        if($m_wm_dada_company)
        {
            if($wmDadaCompanyDao->update($fdata,['id'=>$m_wm_dada_company->id])!==false)
            {
                //清除缓存
                (new wmDadaCompanyCache(['aid'=>$this->s_user->aid]))->delete();
                $this->json_do->set_msg('保存成功');
                $this->json_do->out_put();
            }
            else
                $this->json_do->set_error('005','保存失败');
        }
        else
        {
            $fdata['aid']=$this->s_user->aid;
            if($wmDadaCompanyDao->create($fdata))
            {
                $this->json_do->set_msg('保存成功');
                $this->json_do->out_put();
            }
            else
                $this->json_do->set_error('005','保存失败');
        }
    }
    /**
     * 商户信息编辑-废弃
     * @return [type] [description]
     */
    public function update_status()
    {
        $rules=[
            ['field'=>'is_use','label'=>'启用状态','rules'=>'trim|required|in_list[0,1]']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $fdata['is_use']=(int)$fdata['is_use'];

        $wmDadaCompanyDao = WmDadaCompanyDao::i($this->s_user->aid);
        $m_wm_dada_company = $wmDadaCompanyDao->getOne(['aid'=>$this->s_user->aid]);
        if($m_wm_dada_company)
        {
            if($wmDadaCompanyDao->update($fdata,['id'=>$m_wm_dada_company->id])!==false)
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
            if($wmDadaCompanyDao->create($fdata))
            {
                $this->json_do->set_msg('保存成功');
                $this->json_do->out_put();
            }
            else
                $this->json_do->set_error('005','保存失败');
        }
    }
    /*
    添加门店配置 编辑与配置
     */
    public function shop_edit()
    {
        $rules=[
            ['field'=>'shop_id','label'=>'门店id','rules'=>'trim|required|numeric'],
            ['field'=>'city_code','label'=>'订单所属区域编号','rules'=>'trim|required'],
            ['field'=>'city_name','label'=>'订单所属区域名称','rules'=>'trim|required'],
            ['field'=>'shop_no','label'=>'达达门店编号','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmDadaShopDao = WmDadaShopDao::i($this->s_user->aid);
        $m_wm_dada_shop = $wmDadaShopDao->getOne(['shop_id'=>$fdata['shop_id']]);
        if($m_wm_dada_shop)
        {
            if($wmDadaShopDao->update($fdata,['id'=>$m_wm_dada_shop->id])!==false)
            {
                //编辑时,删除配置缓存
                (new wmDadaCompanyCache(['aid'=>$this->s_user->aid,'shop_id'=>$m_wm_dada_shop->shop_id]))->delete();
                $this->json_do->set_msg('保存成功');
                $this->json_do->out_put();
            }
            else
                $this->json_do->set_error('005','保存失败');
        }
        else
        {
            $fdata['aid']=$this->s_user->aid;
            if($wmDadaShopDao->create($fdata))
            {
                $this->json_do->set_msg('保存成功');
                $this->json_do->out_put();
            }
            else
                $this->json_do->set_error('005','保存失败');
        }
    }
    /*
    所属区域
     */
    public function city_all_list()
    {
        $wmDadaCityDao = WmDadaCityDao::i();
        $list=$wmDadaCityDao->getAllArray();
        $data['rows']=$list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 配置的达达门店列表
     * @return [type] [description]
     */
    public function shop_list()
    {
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->fields= 'a.*,b.shop_name';
        $p_conf->table = "{$wmShardDb->tables['wm_dada_shop']} a left join {$wmShardDb->tables['wm_shop']} b on a.shop_id=b.id ";
        $p_conf->where = " a.aid={$this->s_user->aid} ";
        $p_conf->order = 'a.id desc';

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

}