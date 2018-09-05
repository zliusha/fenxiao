<?php
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\Bll\Main\MainShopAccountBll;
/**
 * @Author: binghe
 * @Date:   2018-07-24 10:09:19
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 16:44:02
 */
/**
 *  门店
 */
class Shop extends wm_service_controller
{
    /**
     * 店铺切换
     * @param  [type] $shopId [description]
     * @return [type]         [description]
     */
    public function toggle($shopId = null)
    {
        //1.获取mainShopIds,总账号为空
        $sUser = $this->s_user;
        $mainShopAccountBll = new MainShopAccountBll();
        $params = [
            'aid'=>$sUser->aid,
            'uid'=>$sUser->id,
            'is_admin'=>$sUser->is_admin
        ];
        $mainShopIds = $mainShopAccountBll->getShopIds($params);

        if(!$sUser->is_admin && empty($mainShopIds))
        {
            $data['msg']='子账号没有权限';
            $data['url']=SAAS_URL;
            $this->_load_redirect($data);
        }
        
        $currentShopId = 0;
        //null时自动登录上一次店铺，上一次店铺不存在，则登录第一个店铺
        if ($shopId === null || !is_numeric($shopId)) {
            $lastShopId = get_secret_cookie('c_wm_shop_id',$sUser->id);
            if($lastShopId !== null)
                $currentShopId = $lastShopId;
            elseif(!$sUser->is_admin)    //子账号默认取第一个门店
            {
                $mainShopIdStr = sql_where_in($mainShopIds);
                $where = ' saas_id='.self::SAAS_ID." and aid={$sUser->aid} and shop_id in({$mainShopIdStr})";
                $firstShop =  MainShopRefitemDao::i()->getOne($where,'*');
                if(empty($firstShop))
                {
                    $data['msg']='当前账号没有该应用门店管理权限';
                    $data['url']=SAAS_URL;
                    $this->_load_redirect($data);
                }
                $currentShopId = $firstShop->ext_shop_id;
            }
        } 
        else
            $currentShopId = $shopId;

        if ($currentShopId > 0) {
            $wmShopDao = WmShopDao::i($sUser->aid);
            $mWmShop = $wmShopDao->getOne(['id'=>$currentShopId,'aid'=>$sUser->aid],'id,main_shop_id');
            if($mWmShop)
            {
                if(!$sUser->is_admin && !in_array($mWmShop->main_shop_id, $mainShopIds))
                {
                    $data['msg']='当前账号没有该应用门店管理权限';
                    $data['url']=SAAS_URL;
                    $this->_load_redirect($data);
                }
            }
            else
            {
                $data['msg']='当前账号没有该应用门店管理权限';
                $data['url']=SAAS_URL;
                $this->_load_redirect($data);
            }
            
        } elseif(!$sUser->is_admin) {
            $data['msg']='子账号没有权限';
            $data['url']=SAAS_URL;
            $this->_load_redirect($data);
        }
        //设置永久cookie,成功时直接跳转
        set_secret_cookie('c_wm_shop_id',$currentShopId,3600*24*7,$sUser->id);
        redirect(DJADMIN_URL);
    }
	/**
	 * 店铺切换
	 * @param  [type] $shopId [description]
	 * @return [type]         [description]
	 */
	public function old_toggle($shopId=null)
	{
		$sUser = $this->s_user;

        $mainShopAccountBll = new MainShopAccountBll();
        $params = [
            'aid'=>$sUser->aid,
            'uid'=>$sUser->id,
            'is_admin'=>$sUser->is_admin
        ];
        $mainShopIds = $mainShopAccountBll->getShopIds($params);
        
		$currentShopId = 0;
        //null时自动登录上一次店铺，上一次店铺不存在，则登录第一个店铺
        if($shopId === null || !is_numeric($shopId))
        {
            $lastShopId = get_secret_cookie('c_wm_shop_id');
            if($lastShopId === null)
            {
                    
                //子账号默认取第一门店
                if(!$sUser->is_admin)
                {
                    
                    if(empty($mainShopIds))
                    {
                        $data['msg']='当前账号没有门店管理权限';
                        $data['url']=SAAS_URL;
                        $this->_load_redirect($data);
                    }
                    $mainShopIdStr = sql_where_in($mainShopIds);
                    $where = ' saas_id='.self::SAAS_ID." and aid={$sUser->aid} and shop_id in({$mainShopIdStr})";
                    $firstShop =  MainShopRefitemDao::i()->getOne($where,'*');
                    if(empty($firstShop))
                    {
                        $data['msg']='当前账号没有该应用门店管理权限';
                        $data['url']=SAAS_URL;
                        $this->_load_redirect($data);
                    }
                    $currentShopId = $firstShop->ext_shop_id;
                }
            }
            elseif($lastShopId != 0)
            {
                $wmShopDao = WmShopDao::i($sUser->aid);
                $mWmShop = $wmShopDao->getOne(['id'=>$lastShopId,'aid'=>$sUser->aid],'id,main_shop_id');
                if($mWmShop)
                {
                    if($sUser->is_admin || in_array($mWmShop->main_shop_id, $mainShopIds))
                        $currentShopId=$mWmShop->id;
                }
                else
                {
                    $data['msg']='当前账号没有该应用门店管理权限';
                    $data['url']=SAAS_URL;
                    $this->_load_redirect($data);
                }
            }
            elseif(!$sUser->is_admin)
            {
                $data['msg']='子账号没有权限';
                $data['url']=SAAS_URL;
                $this->_load_redirect($data);
            }
        }
        elseif($shopId != 0 ){

            $wmShopDao = WmShopDao::i($sUser->aid);
            $mWmShop = $wmShopDao->getOne(['id'=>$shopId,'aid'=>$sUser->aid],'id,main_shop_id');
            if($mWmShop)
            {
                if($sUser->is_admin || in_array($mWmShop->main_shop_id, $mainShopIds))
                    $currentShopId=$mWmShop->id;
            }
            else
            {
                $data['msg']='当前账号没有该应用门店管理权限';
                $data['url']=SAAS_URL;
                $this->_load_redirect($data);
            }

        }
        elseif(!$sUser->is_admin)
        {
            $data['msg']='子账号没有权限';
            $data['url']=SAAS_URL;
            $this->_load_redirect($data);
        }
        //设置永久cookie,成功时直接跳转
        set_secret_cookie('c_wm_shop_id',$currentShopId,3600*24*7);
        redirect(DJADMIN_URL);
	}
    /*
    加载跳转过渡页
     */
    private function _load_redirect($data)
    {
        $this->load->view('common/redirect',$data);
        $this->output->_display();
        exit;
    }
}