<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 评价
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCommentsDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmCommentTagDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCommentReplyDao;
class Comment_api extends wm_service_controller
{


    public function comment_list()
    {

        $shop_id = $this->input->get('shop_id');
        $time = $this->input->get('time');
        $reply_type = $this->input->get("reply_type");//1 未回复 2已回复
        $score = $this->input->get("score");
        $tag = $this->input->get("tag");
        $is_have = $this->input->get("is_have");
        if (!is_numeric($shop_id)) {
          $this->json_do->set_error('001', '参数错误');
        }
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->table =  "{$wmShardDb->tables['wm_comments']}";
        $p_conf->fields = 'DISTINCT order_id,id,aid,shop_id';
        $p_conf->where = " aid={$this->s_user->aid}  AND type=1";
        $p_conf->order = 'id desc';

        $s_time = 0;
        $e_time = 0;
        // 按创建时间范围查询
        if (!empty($time)) {
          $range = explode(' - ', $time);
          $range = array_map('strtotime', $range);
          if (count($range) == 2)
          {
              $range[1] += 86400;
              $p_conf->where .= " AND `time` >= '{$range[0]}' AND `time` <= '{$range[1]}' ";

              $s_time = $range[0];
              $e_time = $range[1];
          }
        }


        //子账号
        if(!$this->is_zongbu)
        {
            $shop_id = $this->currentShopId;
            //未回复
            if(in_array($reply_type,[1]))
                $p_conf->where .=" AND reply_id=0";
            //已回复
            elseif(in_array($reply_type,[2]))
                $p_conf->where .=" AND reply_id>0";

            //评级
            if (is_numeric($score) && in_array($score, [1,2,3,4,5]))
            {
                $p_conf->where .=" AND score={$score} ";
            }
            if(is_numeric($tag) && $tag > 0)
            {
              $p_conf->where .= " AND CONCAT(',',tags, ',') LIKE CONCAT('%,', $tag, ',%')";
            }

        }
        else
        {
            //未回复差评
            if(in_array($reply_type,[1]))
              $p_conf->where .=" AND reply_id=0 AND score in (1,2)";

        }
        if($is_have == 1)
        {
            $p_conf->where .=" AND content != ''";
        }
        //获取店铺条件
        if($shop_id > 0)
        {
            $p_conf->where .= " AND shop_id={$shop_id} ";
        }

        $count = 0;
        $_list = $page->getList($p_conf, $count);

        $order_id_arr = array_column($_list, 'order_id');
        $order_ids = trim(implode(',',$order_id_arr), ',');
        if(empty($order_ids)) $order_ids = '0';

        //获取相应评论结果
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $wmCommentsDao = WmCommentsDao::i($this->s_user->aid);
        $wmCommentReplyDao = WmCommentReplyDao::i($this->s_user->aid);
        $wmCommentTagDao = WmCommentTagDao::i();

        $wm_comments = $wmCommentsDao->getAllArray("aid={$this->s_user->aid}  AND order_id in ({$order_ids})");
        $wm_comment_replys = $wmCommentReplyDao->getAllArray("aid={$this->s_user->aid}  AND order_id in ({$order_ids})");
        $shop_list = $wmShopDao->getAllArray(['aid'=>$this->s_user->aid, 'is_delete'=>0], 'id,aid,shop_name');

        $list = [];
        foreach($_list as $order)
        {
            $tmp['comments'] = array_find_list($wm_comments, 'order_id', $order['order_id']);
            $tmp['reply'] = array_find($wm_comment_replys, 'order_id', $order['order_id']);
            $shop =  array_find($shop_list, 'id', $order['shop_id']);
            $tmp['shop_name'] = isset($shop['shop_name']) ? $shop['shop_name'] : '';
            $tmp['time'] = date('Y-m-d H:i:s', $tmp['comments'][0]['time']);

            $list[] = $tmp;
        }

        //子账号额外数据
        if(!$this->is_zongbu)
        {
            $score = [];
            $where = " AND shop_id={$this->currentShopId}";
            if(!empty($s_time) && !empty($e_time))
            {
              $where .= " AND `time` >= {$s_time} AND `time` <= {$e_time}";
            }
            //已回复
            $reply['done_reply_count'] =  $wmCommentsDao->getCount("aid={$this->s_user->aid}  AND type=1 AND reply_id>0 {$where}");
            //未回复
            $reply['no_reply_count'] =  $wmCommentsDao->getCount("aid={$this->s_user->aid} AND type=1 AND reply_id=0 {$where}");

            //评级 1 星
            $score['score_one_count'] = $wmCommentsDao->getCount("aid={$this->s_user->aid} AND type=1 AND score=1 {$where}");
            //评级 2 星
            $score['score_two_count'] = $wmCommentsDao->getCount("aid={$this->s_user->aid} AND type=1 AND score=2 {$where}");
            //评级 3 星
            $score['score_three_count'] = $wmCommentsDao->getCount("aid={$this->s_user->aid} AND type=1 AND score=3 {$where}");
            //评级 4 星
            $score['score_four_count'] = $wmCommentsDao->getCount("aid={$this->s_user->aid} AND type=1 AND score=4 {$where}");
            //评级 5 星
            $score['score_five_count'] = $wmCommentsDao->getCount("aid={$this->s_user->aid} AND type=1 AND score=5 {$where}");

            //差评回复数量
            $low_score_reply_count = $wmCommentsDao->getCount("aid={$this->s_user->aid} AND type=1 AND score in(1,2) AND reply_id>0 {$where}");


            $data['reply'] = $reply;
            $data['score'] = $score;
            $data['reply_rate'] = format_rate($reply['done_reply_count'], $reply['done_reply_count']+$reply['no_reply_count']);
            $data['low_score_reply_rate'] = format_rate($low_score_reply_count, $score['score_one_count']+$score['score_two_count']);

        }

        $data['comment_tag'] = $wmCommentTagDao->getAllArray(['type'=>1]);

        $data['total'] = $count;
        $data['rows'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


  /**
   * 添加商家回复
   */
    public function reply()
    {
        $rule = [
          ['field' => 'order_id', 'label' => '订单ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'content', 'label' => '回复内容', 'rules' => 'trim']
        ];
        if($this->check->check_ajax_form($rule, false) === false)
        {
          $this->json_do->set_error('002', validation_errors());
        }
        $f_data = $this->form_data($rule);

        $wmCommentsDao = WmCommentsDao::i($this->s_user->aid);
        $m_wm_comment = $wmCommentsDao->getOne(['aid'=>$this->s_user->aid, 'order_id'=>$f_data['order_id']]);
        if(!$m_wm_comment)
        {
            $this->json_do->set_error('002', '评论不存在');
        }

        $data['aid'] = $this->s_user->aid;
        $data['shop_id'] = $m_wm_comment->shop_id;
        $data['order_id'] = $f_data['order_id'];
        $data['uid'] = $m_wm_comment->uid;
        $data['content'] = $f_data['content'];
        $data['time'] = time();

        $wmCommentReplyDao = WmCommentReplyDao::i($this->s_user->aid);
        $id = $wmCommentReplyDao->create($data);
        if($id > 0)
        {
            $wmCommentsDao->update(['reply_id'=>$id], ['order_id'=>$f_data['order_id']]);
          $this->json_do->set_data('修改成功');
          $this->json_do->out_put();

        }

        $this->json_do->set_error('005', '修改失败');
    }


    /**
     * 修改隐藏状态
     */
    public function hide()
    {
        $rule = [
          ['field' => 'order_id', 'label' => '订单ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'is_hide', 'label' => '隐藏状态', 'rules' => 'trim|required|numeric']
        ];
        if($this->check->check_ajax_form($rule, false) === false)
        {
          $this->json_do->set_error('002', validation_errors());
        }
        $f_data = $this->form_data($rule);
        $order_id = $f_data['order_id'];
        if(!in_array($f_data['is_hide'], [0,1]))
        {
            $this->json_do->set_error('002', '参数错误');
        }

        $wmCommentsDao = WmCommentsDao::i($this->s_user->aid);
        if($wmCommentsDao->update(['is_hide'=>$f_data['is_hide']], ['order_id'=>$order_id, 'aid'=>$this->s_user->aid]) === false)
        {
            $this->json_do->set_error('005', '修改失败');
        }

        $this->json_do->set_data('修改成功');
        $this->json_do->out_put();
    }

}
