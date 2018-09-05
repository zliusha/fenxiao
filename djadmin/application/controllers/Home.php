<?php
/**
 * @Author: binghe
 * @Date:   2017-08-04 15:04:27
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 11:32:31
 */
use Service\Cache\Wm\WmServiceCache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\Bll\Main\MainShopAccountBll;
/**
* 
*/
class Home extends base_controller
{

    // 首页
    public function index()
    {
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $model = $mainCompanyAccountDao->getOne(['id'=>$this->s_user->id], 'aid, id,username,img,sex');
        $model->img = conver_picurl($model->img);
        $shop_model = null;
        if(!$this->is_zongbu)
        {
            $wmShopDao = WmShopDao::i($this->s_user->aid);
            $shop_model = $wmShopDao->getOne(['id'=>$this->currentShopId]);
            $shop_model->shop_logo = conver_picurl($shop_model->shop_logo);
        }
        $wmServiceCache = new WmServiceCache(['aid'=>$this->s_user->aid]);
        $service = $wmServiceCache->getDataASNX();
        $shopList = $this->_shop_list();
        $this->load->view('main/index', ['model'=>$model, 'shop_model'=>$shop_model,'service_model'=>$service,'shop_list' => $shopList]);
    }
    /**
     * 当前门店
     * @return [type] [description]
     */
    private function _shop_list()
    {
        $list=[];
        $where = " aid={$this->s_user->aid} and is_delete=0 ";
        if($this->s_user->is_admin)
        {
            $firstItem = ['shop_id'=>0,'shop_name'=>'总店'];
            array_push($list,$firstItem);
        }
        else
        {
            $mainShopAccountBll = new MainShopAccountBll();
            $params = [
                'aid'=>$this->s_user->aid,
                'uid'=>$this->s_user->id,
                'is_admin'=>$this->s_user->is_admin
            ];
            $mainShopIds = $mainShopAccountBll->getShopIds($params);

            $inStr = sql_where_in($mainShopIds);
            $where.= " and main_shop_id in ({$inStr})";
        }
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $rows = $wmShopDao->getAllArray($where,'id as shop_id,shop_name');
        $list = array_merge($list,$rows);
        return $list;
    }
    // 服务订购
    public function price()
    {
        $this->load->view('main/price');
    }
    
    // scrm
    public function scrm()
    {
        $this->load->view('main/scrm');
    }
}