<?php
/**
 * @Author: binghe
 * @Date:   2017-12-26 14:58:30
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:46
 */
/**
* shop
*/
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPromotionDao;
class Shop extends xcx_controller
{
    /**
     * 门店列表
     */
    public function list()
    {
        $wmShopDao = WmShopDao::i($this->aid);
        $wmPromotionDao = WmPromotionDao::i($this->aid);
        $list=$wmShopDao->getAllArray("aid={$this->aid} AND status=0 AND is_delete=0 AND (type&1)>0");

        $time = time();
        //获取有效的活动
        $promotion_list = $wmPromotionDao->getAllArray("aid={$this->aid} AND type=2 AND status=2 AND start_time < {$time} AND end_time > {$time}", 'id,shop_id,setting');

        foreach($list as $key => $shop)
        {
            $list[$key]['time'] = date('Y-m-d', $shop['time']);
            $list[$key]['shop_logo'] = conver_picurl($shop['shop_logo']);

            //添加店铺的活动信息
            $list[$key]['promotion_setting'] = null;
            //全部门店活动
            $promotion = array_find($promotion_list, 'shop_id', 0);
            //当前门店活动
            $_promotion = array_find($promotion_list, 'shop_id', $shop['id']);

            $promotion = $_promotion ? $_promotion : $promotion;
            if($promotion)
              $list[$key]['promotion_setting'] = array_sort(json_decode($promotion['setting']), 'price');
        }


        $data['rows'] = $list;
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 店铺详情
     */
    public function info()
    {
        $shop_id = $this->input->post_get('shop_id');

        $wmShopDao = WmShopDao::i($this->aid);
        $wmOrderDao = WmOrderDao::i($this->aid);
        $wmPromotionDao = WmPromotionDao::i($this->aid);

        $model = $wmShopDao->getOne(['id' => $shop_id, 'aid'=> $this->aid]);
        if(!$model)
        {
          $this->json_do->set_error('001', '未找到相关店铺');
        }

        //商品访问统计
        $uid = isset($this->s_user->uid) ? $this->s_user->uid : 0;
        wm_access_bll::shop($this->aid, $shop_id, $uid);

        $_before_time = strtotime(date('Y-m-d')) - 30*24*3600;
        $model->order_count = $wmOrderDao->getCount("aid={$this->aid} AND shop_id={$shop_id} AND time >= {$_before_time}");

        $model->shop_logo = conver_picurl($model->shop_logo);
        $model->bg_img = conver_picurl($model->bg_img);


        $time = time();
        //全部门店活动
        $m_promotion = $wmPromotionDao->getOne("aid={$this->aid} AND shop_id=0 AND type=2 AND status=2 AND start_time < {$time} AND end_time > {$time}", '*', 'id desc');
        //当前门店活动
        $_m_promotion = $wmPromotionDao->getOne("aid={$this->aid} AND shop_id={$shop_id} AND type=2 AND status=2 AND start_time < {$time} AND end_time > {$time}", '*', 'id desc');

        $m_promotion = $_m_promotion ? $_m_promotion : $m_promotion;

        if($m_promotion)
        {
          $m_promotion->setting = array_sort(json_decode($m_promotion->setting), 'price');
        }

        //处理图片路径
        $shop_imgs_arr = conver_picarr(explode(',', trim($model->shop_imgs, ',')));
        $model->shop_imgs = implode(',', $shop_imgs_arr);

        $data['shop'] = $model;
        $data['promotion'] = $m_promotion;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 读取配送方式
     */
    public function shipping_method()
    {
        $mainCompanyDao = MainCompanyDao::i();
        $shipping_info = $mainCompanyDao->getOneArray(['id' => $this->aid], 'shipping');

        $inc = &inc_config('waimai');

        $shipping_info['shipping_method'] = isset($inc['logistics_type'][$shipping_info['shipping']]) ? $inc['logistics_type'][$shipping_info['shipping']] : '未知配送方式';

        $this->json_do->set_data($shipping_info);
        $this->json_do->out_put();
    }

}