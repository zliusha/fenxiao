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
class Comment extends xcx_user_controller
{
    public function __construct()
    {
        $filterMethods=['comment_list'];
        parent::__construct($filterMethods);
    }
    // public function index()
    // {
    //     $goods_comment_data = [
    //         [
    //           'score'=>'评分',
    //           'tags'=>'标签',
    //           'content'=>'评论内容',
    //           'goods_id'=>'商品ID',
    //           'ext'=>'扩展'
    //         ]
    //       ];
    // }
    
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
        $goods_comment_data = @json_decode($f_data['goods_comment_data'],true);
        foreach($goods_comment_data as $goods_comment)
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
        $order_id = $this->input->post_get('order_id');
        if(!is_numeric($order_id))
          $this->json_do->set_error('002', '参数错误');
        $wmOrderDao = WmOrderDao::i($this->aid);
        $m_wm_order = $wmOrderDao->getOne(['id'=>$order_id,'aid'=>$this->aid], 'id,shop_id,aid');
        if(!$m_wm_order)
        {
            $this->json_do->set_error('002', '订单不存在');
        }

        $wmOrderExtDao = WmOrderExtDao::i($this->aid);
        $list = $wmOrderExtDao->getAllArray(['order_id'=>$order_id,'aid'=>$this->aid]);

        $wmShopDao = WmShopDao::i($this->aid);
        $shop_model = $wmShopDao->getOne(['id'=>$m_wm_order->shop_id]);
        if($shop_model)
          $shop_model->shop_logo = conver_picurl($shop_model->shop_logo);

        $wmCommentTagDao = WmCommentTagDao::i();
        $data['goods_list'] = $list;
        $data['shop_model'] = $shop_model;
        $data['comment_tag'] = $wmCommentTagDao->getAllArray();
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

  /**
   * 评论列表
   */
    public function comment_list()
    {

        $shop_id = $this->input->post_get('shop_id');

        $score_type = $this->input->post_get("score_type");//1,2,3
        if (!is_numeric($shop_id))
        {
          $this->json_do->set_error('001', '参数错误');
        }

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

        $data['score'] = $score;
        $data['total'] = $count;
        $data['rows'] = $list;

        $wmCommentTagDao = WmCommentTagDao::i();
        $data['comment_tag'] = $wmCommentTagDao->getAllArray();
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
