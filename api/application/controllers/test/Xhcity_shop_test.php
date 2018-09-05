<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/18
 * Time: 15:19
 */
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserOrderDao;
class Xhcity_shop_test extends Xhcity_test_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->login();
    }

    public function get_list()
    {
        $params['lat'] = 30.28;
        $params['long'] = 120.15;
        $params['city_code'] = 330106;
        $params['sort_field'] = 'distance';
        $params['sort_type'] = 'asc';

        $url = API_URL . 'xhcity/shop/get_list';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function detail()
    {
        $params['aid'] = 1226;
        $params['shop_id'] = 1;
        $params['lat'] = 30.28;
        $params['long'] = 120.15;

        $url = API_URL . 'xhcity/shop/detail';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }



    public function test()
    {
        $hcityShopExtDao = HcityShopExtDao::i(['aid'=>1226]);
        $shcityOrderDao = ShcityOrderDao::i();
        $shcityUserOrderDao = ShcityUserOrderDao::i(['uid'=>2]);

        $HcityMainDb  = HcityMainDb::i();
        $aidhcityShardDb = HcityShardDb::i(['aid'=>1226]);
        $uidhcityShardDb = HcityShardDb::i(['uid'=>2]);
        $uidhcityShardDb->trans_start();
        $aidhcityShardDb->trans_start();
        $HcityMainDb->trans_start();



        $hcityShopExtDao->update(['collect_num'=>99], ['aid'=>1226, 'shop_id'=>2]);
        $shcityOrderDao->update(['use_end_time'=>999], ['aid'=>1226, 'id'=>2]);
        $shcityUserOrderDao->update(['status_code'=>99], ['uid'=>2]);

        if($uidhcityShardDb->trans_status() === FALSE || $aidhcityShardDb->trans_status() === FALSE || $HcityMainDb->trans_status() === FALSE )
        {
            $aidhcityShardDb->trans_rollback();
            $uidhcityShardDb->trans_rollback();
            $HcityMainDb->trans_rollback();
            echo 'false';exit;
        }

        $aidhcityShardDb->trans_commit();
        $uidhcityShardDb->trans_commit();
        $HcityMainDb->trans_commit();

        echo 'ok';

    }
}