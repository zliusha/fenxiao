<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><?php if($this->is_zongbu):?>
    <ol class="breadcrumb">
      <li><a href="<?=DJADMIN_URL?>mshop/shop">门店管理</a></li>
      <li class="active">桌位管理</li>
    </ol>
  <?php endif;?>
  <ul class="nav nav-tabs">
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/shop_area/table') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/shop_area/table/<?=$shop_id?>">桌位管理</a></li>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/shop_area/index') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/shop_area/index/<?=$shop_id?>">区域管理</a></li>
  </ul>