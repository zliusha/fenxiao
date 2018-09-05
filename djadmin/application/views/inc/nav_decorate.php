<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><?php if($this->is_zongbu):?>
    <ol class="breadcrumb">
      <li><a href="<?=DJADMIN_URL?>mshop/shop">门店管理</a></li>
      <li class="active">外卖设置</li>
    </ol>
  <?php endif;?>
  <ul class="nav nav-tabs">
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/decorate/index') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/decorate/index/<?=$shop_id?>">门店招牌</a></li>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/decorate/poster') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/decorate/poster/<?=$shop_id?>">门店海报</a></li>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/decorate/recommend') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/decorate/recommend/<?=$shop_id?>">推荐商品</a></li>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/decorate/setting') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/decorate/setting/<?=$shop_id?>">配送方式</a></li>
  </ul>