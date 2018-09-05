<?php
/**
 * @Author: binghe
 * @Date:   2017-12-25 16:21:57
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-14 10:28:22
 */
use Service\Cache\Wm\WmServiceCache;
use Service\DbFrame\DataBase\MainDbModels\MainSalelogDao;
use Service\Enum\SaasEnum;
use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmServiceDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmServiceAccountDao;
use Service\Bll\Wm\WmServiceAccountBll;
/**
* Service
*/
class Service extends open_controller
{
    /**
     * 服务列表
     * @return [type] [description]
     */
    public function list()
    {
        $wmServiceDao = WmServiceDao::i();
        $rows = $wmServiceDao->getAllArray('status=1','*','item_id asc');
        $newRows=[];
        foreach ($rows as $row) {
            $newRow = [];
            $newRow['service_id'] = $row['item_id'];
            $newRow['type_name'] = $row['item_name'];
            $newRow['is_trial'] = 0;
            $newRow['shop_limit'] = $row['shop_limit'];
            array_push($newRows, $newRow);
        }
        $data['rows'] = $newRows;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 公司当前版本信息
     * @return [type] [description]
     */
    public function company_info()
    { 
        $rules=[
            ['field'=>'visit_id','label'=>'公司id','rules'=>'required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $aid = $this->convert_visit_id($fdata['visit_id']);

        $mainSaasAccountDao = MainSaasAccountDao::i();
        $mMainSaasAccount = $mainSaasAccountDao->getOne(['aid'=>$aid,'saas_id'=>SaasEnum::YD]);
        if($mMainSaasAccount)
        {
            $info = new stdClass();
            $info->id = $mMainSaasAccount->id;
            $info->aid = $mMainSaasAccount->aid;
            $info->service_id = $mMainSaasAccount->saas_item_id;
            $info->service_day_limit = $mMainSaasAccount->expire_time;
            $info->service_type_name = $mMainSaasAccount->saas_item_name;
            $info->is_trial = 0 ;//新版永为false
            $info->shop_limit = 1;//默认等于１
            $mWmService = WmServiceDao::i()->getOne(['item_id'=>$mMainSaasAccount->saas_item_id]);
            if($mWmService)
                $info->shop_limit = $mWmService->shop_limit;
            $mWmServiceAccount = WmServiceAccountDao::i()->getOne(['aid'=>$mMainSaasAccount->aid]);
            if($mWmServiceAccount)
                $info->shop_limit = $mWmServiceAccount->shop_limit;
            $data['info']=$info;
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('004','未订购服务');
    }
    /**
     * 变更服务
     * @return [type] [description]
     */
    public function change()
    {
        $rules=[
            ['field'=>'visit_id','label'=>'公司id','rules'=>'required|numeric']
            ,['field'=>'service_id','label'=>'版本id','rules'=>'required|numeric']
            ,['field'=>'service_day_limit','label'=>'服务结束日期','rules'=>'required|preg_key[DATE]']
            ,['field'=>'shop_limit','label'=>'','rules'=>'required|is_natural_no_zero']
            ,['field'=>'money','label'=>'涉及金额','rules'=>'trim|required|preg_key[PRICE]']
            ,['field'=>'sale_name','label'=>'销售人员名称','rules'=>'trim']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $aid = $this->convert_visit_id($fdata['visit_id']);

        $mainSaasAccountDao = MainSaasAccountDao::i();
        $mMainSaasAccount = $mainSaasAccountDao->getOne(['saas_id'=>SaasEnum::YD,'aid'=>$aid]);
        if(!$mMainSaasAccount)
            $this->json_do->set_error('004','未订购服务');
        $wmServiceDao = WmServiceDao::i();
        $mWmService = $wmServiceDao->getOne(['item_id'=>$fdata['service_id']]);
        if(!$mWmService)
            $this->json_do->set_error('004','该服务不存在');
        $update['expire_time'] = $fdata['service_day_limit'];
        $update['saas_item_id']=$mWmService->item_id;
        $update['saas_item_name'] = $mWmService->item_name;
        $update['update_time'] = time();
        if($mainSaasAccountDao->update($update,['id'=>$mMainSaasAccount->id])!==false)
        {
            //更新门店限制
            if($mWmService->shop_limit != $fdata['shop_limit'])
            {
                (new WmServiceAccountBll())->updateShopLimit($aid,$fdata['shop_limit']);
            }
            //记录销售记录
            $loginput['aid']=$aid;
            $loginput['money']=$fdata['money'];
            $loginput['sale_name']=$fdata['sale_name'];
            $loginput['des'] = "云店版本变更:{$mainSaasAccountDao->saas_item_id}=>{$mWmService->item_id},{$mMainSaasAccount->expire_time}=>{$fdata['service_day_limit']},门店限制数量=>{$fdata['shop_limit']}";
            $this->_sale_log($loginput);
            //删除当前aid服务缓存
            $this->_delete_cache($aid);
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005','变更服务失败');
    }
    /**
     * 添加门店数量
     */
    public function add_shop_limit()
    {
        $rules=[
        ['field'=>'visit_id','label'=>'visit_id','rules'=>'trim|required|numeric']
        ,['field'=>'add_shop_num','label'=>'添加门店数量','rules'=>'trim|required|is_natural_no_zero']
        ,['field'=>'money','label'=>'涉及金额','rules'=>'trim|required|preg_key[PRICE]']
        ,['field'=>'sale_name','label'=>'销售人员名称','rules'=>'trim']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $aid = $this->convert_visit_id($fdata['visit_id']);

        $mainSaasAccountDao = MainSaasAccountDao::i();
        $mMainSaasAccount = $mainSaasAccountDao->getOne(['saas_id'=>SaasEnum::YD,'aid'=>$aid]);
        if(!$mMainSaasAccount)
            $this->json_do->set_error('004','未订购服务');
        $wmServiceDao = WmServiceDao::i();
        $mWmService = $wmServiceDao->getOne(['item_id'=>$mMainSaasAccount->saas_item_id]);
        if(!$mWmService)
            $this->json_do->set_error('004','该服务不存在');

        $shopLimit = $fdata['add_shop_num'];
        $wmServiceAccountDao = WmServiceAccountDao::i();
        $mWmServiceAccount = $wmServiceAccountDao->getOne(['aid'=>$aid]);
        $oldShopLimit=0;
        if($mWmServiceAccount)
            $oldShopLimit=$mWmServiceAccount->shop_limit;
        else
            $oldShopLimit=$mWmService->shop_limit;
        $shopLimit = $shopLimit+$oldShopLimit;
        $wmServiceAccountBll = new WmServiceAccountBll();
        if($wmServiceAccountBll->updateShopLimit($aid,$shopLimit)!==false)
        {
            //记录销售记录
            $loginput['aid']=$aid;
            $loginput['money']=$fdata['money'];
            $loginput['sale_name']=$fdata['sale_name'];
            $loginput['des'] = "云店版本添加门店数量:{$oldShopLimit}=>{$shopLimit}";
            $this->_sale_log($loginput);
            //删除当前aid服务缓存
            $this->_delete_cache($aid);
            $this->json_do->out_put();
        }
    }
    /**
     * 续费-现有服务上增加服务天数
     * [add_days description]
     */
    public function add_days()
    {
        $rules=[
        ['field'=>'visit_id','label'=>'visit_id','rules'=>'trim|required|numeric']
        ,['field'=>'days','label'=>'天数','rules'=>'trim|required|is_natural_no_zero']
        ,['field'=>'money','label'=>'涉及金额','rules'=>'trim|required|preg_key[PRICE]']
        ,['field'=>'sale_name','label'=>'销售人员名称','rules'=>'trim']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $aid = $this->convert_visit_id($fdata['visit_id']);

        $mainSaasAccountDao = MainSaasAccountDao::i();
        $mMainSaasAccount = $mainSaasAccountDao->getOne(['saas_id'=>SaasEnum::YD,'aid'=>$aid]);
        if(!$mMainSaasAccount)
            $this->json_do->set_error('004','未订购服务');
        $wmServiceDao = WmServiceDao::i();
        $mWmService = $wmServiceDao->getOne(['item_id'=>$mMainSaasAccount->saas_item_id]);
        if(!$mWmService)
            $this->json_do->set_error('004','该服务不存在');
        $today = date('Y-m-d');
        if($mMainSaasAccount->expire_time < $today)
            $time = strtotime("+{$fdata['days']} day");
        else
            $time = strtotime($mMainSaasAccount->expire_time) + $fdata['days'] * 3600 * 24;

        $update['update_time'] = time();
        $update['expire_time'] = date('Y-m-d',$time);
        if($mainSaasAccountDao->update($update,['id'=>$mMainSaasAccount->id])!==false)
        {
            //记录销售记录
            $loginput['aid']=$aid;
            $loginput['money']=$fdata['money'];
            $loginput['sale_name']=$fdata['sale_name'];
            $loginput['des'] = "云店版本续费:{$mMainSaasAccount->expire_time}=>{$update['expire_time']}";
            $this->_sale_log($loginput);
            //删除当前aid服务缓存
            $this->_delete_cache($aid);
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005','续费失败');
    }
    /**
     * 销售记录
     * @return [type] [description]
     */
    private function _sale_log($loginput)
    {
        if(!is_array($loginput))
            return false;
        if(!isset($loginput['time']))
            $loginput['time'] = time();
        $mainSalelogDao = MainSalelogDao::i();
        if($mainSalelogDao->create($loginput))
            return true;
        else
            return false;
    }
    /**
     * 删除aid版本缓存
     * @return [type] [description]
     */
    private function _delete_cache($aid)
    {
        (new WmServiceCache(['aid'=>$aid]))->delete();
    }
}