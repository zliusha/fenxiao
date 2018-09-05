<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 门店接口
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPromotionDao;
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmCommentsDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmCommentTagDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCommentReplyDao;
class Shop extends mshop_controller
{

    /**
     * 门店列表查询
     */
    public function get_all()
    {
        $wmShopDao = WmShopDao::i($this->aid);
        $wmPromotionDao = WmPromotionDao::i($this->aid);
        $list = $wmShopDao->getAllArray("aid={$this->aid} AND status=0 AND is_delete=0 AND (type&1)>0");

        $time = time();
        //获取有效的活动
        $promotion_list = $wmPromotionDao->getAllArray("aid={$this->aid} AND type=2 AND status=2 AND start_time < {$time} AND end_time > {$time}", 'id,shop_id,setting', 'id desc');

        foreach ($list as $key => $shop) {
            $list[$key]['time'] = date('Y-m-d', $shop['time']);
            $list[$key]['shop_logo'] = conver_picurl($shop['shop_logo']);

            //添加店铺的活动信息
            $list[$key]['promotion_setting'] = null;
            //全部门店活动
            $promotion = array_find($promotion_list, 'shop_id', 0);
            //当前门店活动
            $_promotion = array_find($promotion_list, 'shop_id', $shop['id']);

            $promotion = $_promotion ? $_promotion : $promotion;
            if ($promotion) {
                $list[$key]['promotion_setting'] = array_sort(json_decode($promotion['setting']), 'price');
            }

        }

        $data['list'] = $list;
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 店铺详情
     */
    public function info()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $shop_id = $f_data['shop_id'];


        //商品访问统计
        $uid = isset($this->s_user->uid) ? $this->s_user->uid : 0;
        wm_access_bll::shop($this->aid, $shop_id, $uid);

        $wmShopDao = WmShopDao::i($this->aid);
        $wmOrderDao = WmOrderDao::i($this->aid);
        $wmPromotionDao = WmPromotionDao::i($this->aid);

        $model = $wmShopDao->getOne(['id' => $shop_id, 'aid'=>$this->aid]);
        if (!$model) {
            $this->json_do->set_error('001', '未找到相关店铺');
        }

        $_before_time = strtotime(date('Y-m-d')) - 30 * 24 * 3600;
        $model->order_count = $wmOrderDao->getCount(" aid={$this->aid} AND shop_id={$shop_id} AND time >= {$_before_time}");

        $model->shop_logo = conver_picurl($model->shop_logo);
        $model->bg_img = conver_picurl($model->bg_img);

        $setting_model = $wmPromotionDao->getOne(['aid' => $this->aid]);
        if($setting_model)
        {
            $setting_model->share_img = conver_picurl($setting_model->share_img);

        }
        $model->setting = $setting_model;

        $this->json_do->set_data($model);
        $this->json_do->out_put();
    }

    /**
     * 活动详情
     */
    public function promotion_info()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $shop_id = $f_data['shop_id'];

        $time = time();
        $wmPromotionDao = WmPromotionDao::i($this->aid);
        //全部门店活动
        $model = $wmPromotionDao->getOne("aid={$this->aid} AND shop_id=0 AND type=2 AND status=2 AND start_time < {$time} AND end_time > {$time}", '*', 'id desc');
        //当前门店活动
        $_model = $wmPromotionDao->getOne("aid={$this->aid} AND shop_id={$shop_id} AND type=2 AND status=2 AND start_time < {$time} AND end_time > {$time}", '*', 'id desc');

        $model = $_model ? $_model : $model;
        if ($model) {
            $model->setting = array_sort(json_decode($model->setting), 'price');
        }


        $this->json_do->set_data($model);
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


    /**
     * 评论列表
     */
    public function comment_list()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'score_type', 'label' => '评论类型', 'rules' => 'trim|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $shop_id = $f_data['shop_id'];
        $score_type = $f_data['score_type'];

        $wmShardDb = WmShardDb::i($this->aid);
        $pageList = new PageList($wmShardDb);
        $pConf = $pageList->getConfig();

        $pConf->table = "{$wmShardDb->tables['wm_comments']}";
        $pConf->fields = 'DISTINCT order_id,id,aid,shop_id';
        $pConf->where = " aid={$this->aid} AND type=1 AND shop_id={$shop_id} AND is_hide=0 ";
        $pConf->order = 'id desc';

        //评级
        if(in_array($score_type,[1, 2, 3]))
        {
            if($score_type == 1)
                $pConf->where .=" AND score in (1,2)";
            if($score_type == 2)
                $pConf->where .=" AND score in (3,4)";
            if($score_type == 3)
                $pConf->where .=" AND score=5";
        }

        $count = 0;
        $list = $pageList->getList($pConf, $count);

        $order_id_arr = array_column($list, 'order_id');
        $order_ids = trim(implode(',',$order_id_arr), ',');
        if(empty($order_ids)) $order_ids = '0';

        //获取相应评论结果
        $wmCommentsDao = WmCommentsDao::i($this->aid);
        $wmCommentReplyDao = WmCommentReplyDao::i($this->aid);
        $wm_comments = $wmCommentsDao->getAllArray("aid={$this->aid} AND order_id in ({$order_ids})");
        $wm_comment_replys = $wmCommentReplyDao->getAllArray("aid={$this->aid} AND order_id in ({$order_ids})");

        $list = [];
        foreach($order_id_arr as $order_id)
        {
            $tmp['comments'] = array_find_list($wm_comments, 'order_id', $order_id);
            $tmp['reply'] = array_find($wm_comment_replys, 'order_id', $order_id);
            $tmp['time'] = date('Y-m-d H:i:s', $tmp['comments'][0]['time']);

            $list[] = $tmp;
        }

        //评级 1 星
        $score['score_one_count'] = $wmCommentsDao->getCount("aid={$this->aid} AND shop_id={$shop_id} AND type=1 AND score=1 ");
        //评级 2 星
        $score['score_two_count'] = $wmCommentsDao->getCount("aid={$this->aid} AND shop_id={$shop_id} AND type=1 AND score=2 ");
        //评级 3 星
        $score['score_three_count'] = $wmCommentsDao->getCount("aid={$this->aid} AND shop_id={$shop_id} AND type=1 AND score=3 ");
        //评级 4 星
        $score['score_four_count'] = $wmCommentsDao->getCount("aid={$this->aid} AND shop_id={$shop_id} AND type=1 AND score=4 ");
        //评级 5 星
        $score['score_five_count'] = $wmCommentsDao->getCount("aid={$this->aid} AND shop_id={$shop_id} AND type=1 AND score=5 ");

        $wmCommentTagDao = WmCommentTagDao::i();
        $data['comment_tag'] = $wmCommentTagDao->getAllArray();

        $data['score'] = $score;
        $data['total'] = $count;
        $data['rows'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
