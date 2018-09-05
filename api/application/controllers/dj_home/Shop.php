<?php

/**
 * @Author: binghe
 * @Date:   2018-07-24 15:00:36
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-26 13:38:46
 */
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Bll\Main\MainShopBll;
use Service\Bll\Main\MainCompanyAccountBll;
/**
 *　门店通管理
 */
class Shop extends dj_controller
{
	public function __construct()
	{
		parent::__construct();
		if(!$this->s_user->is_admin)
			$this->json_do->set_error('004','没有权限');
	}
	/**
	 * 获取门店信息
	 */
	public function info()
	{
		$rules = [
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $mainShopBll = new MainShopBll;
        $mMainShop = $mainShopBll->info($this->s_user->aid,$fdata['shop_id']);
        $data['info'] = $mMainShop;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
	}
	/**
	 * 添加
	 */
	public function add()
	{
		$rules = [
            ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim|required'],
            ['field' => 'shop_logo', 'label' => '门店logo', 'rules' => 'trim|required'],
            ['field' => 'contact', 'label' => '联系方式', 'rules' => 'trim|required'],
            ['field' => 'shop_state', 'label' => '门店所在地-省份', 'rules' => 'trim|required'],
            ['field' => 'shop_city', 'label' => '门店所在地-城市', 'rules' => 'trim|required'],
            ['field' => 'shop_district', 'label' => '门店所在地-区域', 'rules' => 'trim|required'],
            ['field' => 'shop_address', 'label' => '门店所在地-详细地址', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim|required'],
            ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
            ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $mainShopBll = new MainShopBll;
        $id = $mainShopBll->add($this->s_user->aid,$fdata);
        $this->json_do->out_put();
	}
	/**
	 * 编辑
	 */
	public function edit()
	{
		$rules = [
			['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required|numeric'],
            ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim|required'],
            ['field' => 'shop_logo', 'label' => '门店logo', 'rules' => 'trim|required'],
            ['field' => 'contact', 'label' => '联系方式', 'rules' => 'trim|required'],
            ['field' => 'shop_state', 'label' => '门店所在地-省份', 'rules' => 'trim|required'],
            ['field' => 'shop_city', 'label' => '门店所在地-城市', 'rules' => 'trim|required'],
            ['field' => 'shop_district', 'label' => '门店所在地-区域', 'rules' => 'trim|required'],
            ['field' => 'shop_address', 'label' => '门店所在地-详细地址', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim|required'],
            ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
            ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $shopId = $fdata['shop_id'];
        unset($fdata['shop_id']);
        $mainShopBll = new MainShopBll;
        $count = $mainShopBll->edit($this->s_user->aid,$shopId,$fdata);
        $this->json_do->out_put();
	}
	/**
	 * 删除门店，同时删除门店关联的员工记录
	 */
	public function delete()
	{
		$rules = [
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $affectedRows = (new MainShopBll())->delete($this->s_user->aid,$fdata['shop_id']);
        $this->json_do->out_put();
	}
	/**
	 * 门店列表
	 */
	public function list()
	{
		$data = (new MainShopBll())->pageList($this->s_user->aid);
		$this->json_do->set_data($data);
		$this->json_do->out_put();
	}
	/**
	 * 得到门店员工列表
	 * @return [type] [description]
	 */
	public function employee_all()
	{
		$data['rows'] = (new MainCompanyAccountBll())->employeeAllList($this->s_user->aid);
		$this->json_do->set_data($data);
		$this->json_do->out_put();
	}
	/**
	 * 门店添加子账号关联
	 */
	public function add_employee()
	{
		$rules = [
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|required|numeric'],
            ['field' => 'account_id', 'label' => '账号id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        (new MainShopBll())->addEmployee($this->s_user->aid,$fdata['shop_id'],$fdata['account_id']);
        $this->json_do->out_put();
	}
	/**
	 * 删除门店子账号关联
	 * @return [type] [description]
	 */
	public function delete_employee()
	{
		$rules = [
            ['field' => 'shop_account_id', 'label' => '店铺id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        (new MainShopBll())->deleteEmployee($this->s_user->aid,$fdata['shop_account_id']);
        $this->json_do->out_put();
	}
}