<?php
/**
 * @Author: binghe
 * @Date:   2017-10-26 11:45:19
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:48
 */
/**
* 账号api
*/
use Service\DbFrame\DataBase\MainDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
class Account_api extends wm_service_controller
{
    
    /**
     * 账号列表
     * @return [type] [description]
     */
    public function list()
    {
        $mainDb = MainDb::i();
        $page = new PageList($mainDb);
        $p_conf = $page->getConfig();

        $p_conf->fields='a.id,a.aid,a.shop_id,a.username,a.time';
        $p_conf->table = "{$mainDb->tables['main_company_account']} a ";
        $p_conf->where = " a.aid={$this->s_user->aid} and a.is_admin <> 1 ";
        $p_conf->order = 'a.id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        $shop_ids = trim(implode(',',array_column($list, 'shop_id')), ',');
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $shop_list = [];
        if($shop_ids)
            $shop_list = $wmShopDao->getAllArray("aid={$this->s_user->aid} AND id in($shop_ids)");

        foreach($list as $k => $val)
        {
            $list[$k]['time'] = date('Y-m-d H:i:s', $val['time']);

            $shop = array_find($shop_list, 'id', $val['shop_id']);
            $list[$k]['shop_name'] = isset($shop['shop_name']) ? $shop['shop_name'] : '';
        }

        $data['total'] = $count;
        $data['rows'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 修改账号
     * @return [type] [description]
     */
    public function edit()
    {
        $account_id=$this->input->get('account_id');
        if(empty($account_id) || !is_numeric($account_id))
        {
            $this->json_do->set_error('001');
        }

        $rules = [
          ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        //条件:判断门店是否存在
        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $m_wm_shop = $wmOrderDao->getOne(['id'=>$fdata['shop_id'],'aid'=>$this->s_user->aid]);
        if(!$m_wm_shop)
            $this->json_do->set_error('004','修改失败,门店不存在');
        //条件:判断门店是否已添加账号
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $m_main_company_account=$mainCompanyAccountDao->getOne(['shop_id'=>$m_wm_shop->id,'id <>'=>$account_id]);
        if($m_main_company_account)
            $this->json_do->set_error('004','修改失败,该门店已关联账号');

        
        if($mainCompanyAccountDao->update($fdata,['id'=>$account_id,'aid'=>$this->s_user->aid])!==false)
        {
            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        }
        else
        {
            $this->json_do->set_error('005','修改账号信息失败');
        }

    }

    /**
     * 添加账号
     */
    public function add()
    {
        $rules = [
          ['field' => 'user_id', 'label' => '账号id', 'rules' => 'trim|required|numeric'],
          ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        //条件:判断账号是否添加
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $m_main_company_account = $mainCompanyAccountDao->getOne(['user_id'=>$fdata['user_id']]);
        if($m_main_company_account)
            $this->json_do->set_error('004','添加失败,该账号已存在');
        //条件:判断门店是否存在

        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $m_wm_shop = $wmShopDao->getOne(['id'=>$fdata['shop_id'],'aid'=>$this->s_user->aid]);
        if(!$m_wm_shop)
            $this->json_do->set_error('004','添加失败,门店不存在');
        //条件:判断门店是否已添加账号
        $m_shop_account=$mainCompanyAccountDao->getOne(['shop_id'=>$m_wm_shop->id]);
        if($m_shop_account)
            $this->json_do->set_error('004','添加失败,该门店已关联账号');
        try {
            $erp_sdk = new erp_sdk;
            $params[]=$this->s_user->visit_id;
            $params[]=$fdata['user_id'];
            $res=$erp_sdk->getUserById($params);
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
        if($res['user_nature']==1)
            $this->json_do->set_error('总账号不能关联门店');
        $data['shop_id']=$fdata['shop_id'];
        $data['user_id']=$res['user_id'];
        $data['visit_id']=$res['visit_id'];
        $data['aid']=$this->s_user->aid;
        $data['is_admin']=$res['user_nature'];
        $data['username']=$res['user_name'];

        if($mainCompanyAccountDao->create($data))
        {
            $this->json_do->set_msg('添加成功');
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005','添加失败');
        
    }
    /**
     * 账号删除
     * @return [type] [description]
     */
    public function del()
    {
        $rules = [
          ['field' => 'account_id', 'label' => '门店id', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        if($mainCompanyAccountDao->delete(['id'=>$fdata['account_id'],'aid'=>$this->s_user->aid,'is_admin <>'=>1])===false)
            $this->json_do->set_error('005', '删除失败');
        else
        {
            $this->json_do->set_msg('删除成功');
            $this->json_do->out_put();
        }
    }
    
}