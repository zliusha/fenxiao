<?php

/**
 * @Author: binghe
 * @Date:   2018-07-25 15:51:47
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-26 13:26:03
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\Bll\Main\MainCompanyAccountBll;
use Service\Cache\ErpLoginTokenCache;
/**
 * 员工管理
 */
class Employee extends dj_controller
{
	public function __construct()
	{
		parent::__construct();
		if(!$this->s_user->is_admin)
			$this->json_do->set_error('004','没有权限');
	}
	/**
	 * 员工列表
	 */
	public function list()
	{
		$mainCompanyAccountBll = new MainCompanyAccountBll;
		$data = $mainCompanyAccountBll->employeePageList($this->s_user->aid);
		$this->json_do->set_data($data);
		$this->json_do->out_put();
	}
	/**
	 * 添加员工账号
	 */
	public function add()
	{
		$rules = [
          ['field' => 'user_id', 'label' => '账号id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $id=(new MainCompanyAccountBll())->addEmployee($this->s_user->aid,$this->s_user->visit_id,$fdata['user_id']);
        $this->json_do->out_put();
	}
	//删除员工账号,删除员工账号，同时会该员工店铺管理权限
	public function delete()
	{
		$rules = [
            ['field' => 'account_id', 'label' => '账号id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
		$mainCompanyAccountBll = new MainCompanyAccountBll;
		$mainCompanyAccountBll->deleteEmployee($this->s_user->aid,$fdata['account_id']);
		$this->json_do->out_put();
	}
	/**
	 * 获取爱聚账号中心
	 * @return [type] [description]
	 */
	public function get_ajuc_url()
	{
		$erpGetTokenCache = new ErpLoginTokenCache(['visit_id'=>$this->s_user->visit_id,'user_id'=>$this->s_user->user_id]);
        $token = $erpGetTokenCache->getDataASNX();
        $inc = &inc_config('erp');
        $data['url']=$inc['login_url'].'?token='.$token.'&redirectUri=/oanew/#/new/power/1';
        $this->json_do->set_data($data);
        $this->json_do->out_put();
	}
	public function ajuc_employee_list()
	{
		$mainCompanyAccountBll = new MainCompanyAccountBll;
		$data = $mainCompanyAccountBll->ajucEmployeeAllList($this->s_user->aid,$this->s_user->visit_id);
		$this->json_do->set_data($data);
		$this->json_do->out_put();
	}
}