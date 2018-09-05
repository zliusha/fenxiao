<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 财务统计
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmWithdrawDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
class Finance extends wm_service_controller
{
  /**
   * 总店财务账单
   */
    function index()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        if($this->is_zongbu)
        {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        }
        else
        {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
            $m_wm_shop = $wmShopDao->getOne(['id'=>$this->currentShopId]);
            $wmWithdrawDao = WmWithdrawDao::i($this->s_user->aid);
            //提现中的金额
            $on_money = $wmWithdrawDao->getWithdrawMoeny(['shop_id'=>$this->currentShopId,'status'=>0, 'aid'=>$this->s_user->aid]);
            $withdraw_money=$m_wm_shop->money  - $on_money;
            $data['on_money']=sprintf("%.2f", $on_money);
            $data['withdraw_money']=sprintf("%.2f", $withdraw_money);
            $data['money'] = $m_wm_shop->money;

            //待结算金额 = 预计收益金额
            $wmOrderDao = WmOrderDao::i($this->s_user->aid);
            $settlement_money = $wmOrderDao->getSum("expect_settle_money", "shop_id={$this->currentShopId} AND pay_time > 0 AND settle_time = 0 AND expect_settle_money > 0");
            $data["settlement_money"] = sprintf("%.2f", $settlement_money);
        }

        $this->load->view('mshop/finance/index', $data);
    }

      /**
       * 提现申请列表
       */
    function withdraw_list()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 判断子账号权限
        if($this->is_zongbu) {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/finance/withdraw_list', $data);
    }
}
