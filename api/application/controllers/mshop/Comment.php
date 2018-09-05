<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 商品评论接口
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCommentsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmCommentTagDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCommentReplyDao;
class Comment extends mshop_user_controller
{
    /**
    public function index()
    {
        $goods_comment_data = [
            [
              'score'=>'评分',
              'tags'=>'标签',
              'content'=>'评论内容',
              'goods_id'=>'商品ID',
              'ext'=>'扩展'
            ]
          ];
    }
    */
    /**
     * 提交评论
     */
    public function add()
    {
        $rule = [
          ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'order_id', 'label' => '订单ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'business_serv_score', 'label' => '商家服务评分', 'rules' => 'numeric'],
          ['field' => 'business_serv_tags', 'label' => '商家服务标签', 'rules' => 'trim'],
          ['field' => 'business_serv_content', 'label' => '商家服务评论内容', 'rules' => 'trim'],
          ['field' => 'shipping_serv_score', 'label' => '配送服务评分', 'rules' => 'trim'],
          ['field' => 'shipping_serv_tags', 'label' => '配送服务标签', 'rules' => 'trim'],
          ['field' => 'shipping_serv_content', 'label' => '配送服务评论内容', 'rules' => 'trim'],
          ['field' => 'goods_comment_data', 'label' => '商品评论内容', 'rules' => 'trim'],
          ['field' => 'picarr', 'label' => '图片', 'rules' => 'trim']
        ];


        if($this->check->check_ajax_form($rule, false) === false)
        {
          $this->json_do->set_error('002', validation_errors());
        }
        $f_data = $this->form_data($rule);

        $wmOrderDao = WmOrderDao::i($this->aid);
        //判断订单是否评论
        $m_order = $wmOrderDao->getOne(['id'=>$f_data['order_id'], 'uid'=>$this->s_user->uid, 'aid' => $this->aid], 'id,aid,shop_id,uid,is_comment');
        if(!$m_order)
          $this->json_do->set_error('002', '订单不存在');

        if($m_order->is_comment > 0)
          $this->json_do->set_error('002', '已评论');

        $data = [];
        //商家服务评价
        $business_serv_data['aid'] = $this->aid;
        $business_serv_data['shop_id'] = $f_data['shop_id'];
        $business_serv_data['order_id'] = $f_data['order_id'];
        $business_serv_data['score'] = $f_data['business_serv_score'];
        $business_serv_data['tags'] = $f_data['business_serv_tags'];
        $business_serv_data['content'] = $f_data['business_serv_content'];
        $business_serv_data['goods_id'] = 0;
        $business_serv_data['picarr'] = $f_data['picarr'];
        $business_serv_data['ext'] = '';
        $business_serv_data['type'] = 1;
        $business_serv_data['uid'] = $this->s_user->uid;
        $business_serv_data['time'] = time();

        $data[] = $business_serv_data;
        //配送服务评价
        $shipping_serv_data['aid'] = $this->aid;
        $shipping_serv_data['shop_id'] = $f_data['shop_id'];
        $shipping_serv_data['order_id'] = $f_data['order_id'];
        $shipping_serv_data['score'] = $f_data['shipping_serv_score'];
        $shipping_serv_data['tags'] = $f_data['shipping_serv_tags'];
        $shipping_serv_data['content'] = $f_data['shipping_serv_content'];
        $shipping_serv_data['goods_id'] = 0;
        $shipping_serv_data['picarr'] = '';
        $shipping_serv_data['ext'] = '';
        $shipping_serv_data['type'] = 2;
        $shipping_serv_data['uid'] = $this->s_user->uid;
        $shipping_serv_data['time'] = time();

        $data[] = $shipping_serv_data;

        //商品评价
        $f_data['goods_comment_data'] = @json_decode($f_data['goods_comment_data'], true);
        foreach($f_data['goods_comment_data'] as $goods_comment)
        {
            $tmp['aid'] =  $this->aid;
            $tmp['shop_id'] = $f_data['shop_id'];
            $tmp['order_id'] = $f_data['order_id'];
            $tmp['score'] = $goods_comment['score'];
            $tmp['tags'] = $goods_comment['tags'];
            $tmp['content'] = $goods_comment['content'];
            $tmp['goods_id'] = $goods_comment['goods_id'];
            $tmp['picarr'] = '';
            $tmp['ext'] = $goods_comment['ext'];
            $tmp['type'] = 0;
            $tmp['uid'] = $this->s_user->uid;
            $tmp['time'] = time();

            $data[] = $tmp;
        }

        $wmCommentsDao = WmCommentsDao::i($this->aid);
        $wmCommentsDao->createBatch($data);

        //更新订单的是否评论字段
        $wmOrderDao->update(['is_comment'=>1], ['id'=>$m_order->id, 'aid' => $this->aid]);

        $this->json_do->set_msg('保存成功');
        $this->json_do->out_put();
    }

  /**
   * 获取订单商品列表
   */
    public function order_goods_list()
    {
        $rule = [
            ['field' => 'order_id', 'label' => '订单ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'score_type', 'label' => '评论类型', 'rules' => 'trim|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $order_id = $f_data['order_id'];

        $wmOrderDao = WmOrderDao::i($this->aid);
        $m_wm_order = $wmOrderDao->getOne(['id'=>$order_id,'aid'=>$this->aid], 'id,shop_id,aid');
        if(!$m_wm_order)
        {
            $this->json_do->set_error('002', '订单不存在');
        }

        $wmOrderExtDao = WmOrderExtDao::i($this->aid);
        $list = $wmOrderExtDao->getAllArray(['order_id'=>$order_id,'aid'=>$this->aid]);

        $wmShopDao = WmShopDao::i($this->aid);
        $shop_model = $wmShopDao->getOne(['id'=>$m_wm_order->shop_id, 'aid' => $this->aid]);
        if($shop_model)
          $shop_model->shop_logo = conver_picurl($shop_model->shop_logo);

        $wmCommentTagDao = WmCommentTagDao::i();
        $data['goods_list'] = $list;
        $data['shop_model'] = $shop_model;
        $data['comment_tag'] = $wmCommentTagDao->getAllArray();
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

}
