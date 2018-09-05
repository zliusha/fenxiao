<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><ul class="nav nav-tabs">
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/items/index') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/items/index">商品列表</a></li>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/items/cate') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/items/cate">商品分类</a></li>
    <?php if($this->is_zongbu):?>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/store_goods') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/store_goods">商品库</a></li>
    <?php endif;?>
  </ul>