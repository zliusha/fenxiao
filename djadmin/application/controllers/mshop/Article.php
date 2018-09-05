<?php
/**
* 文章系统
*/
class Article extends wm_service_controller
{

  // 文章详情
  public function detail($article_id=0)
  {
      $this->load->view('mshop/article/detail', ['article_id'=>$article_id]);
  }

}