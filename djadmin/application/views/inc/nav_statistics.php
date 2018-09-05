<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><ul class="nav nav-tabs">
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/statistics/trade') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/statistics/trade">营业统计</a></li>
    <?php if( power_exists(module_enum::WM_MODULE,$this->service->power_keys)):?>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/statistics/flow') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/statistics/flow">外卖流量分析</a></li>
    <?php endif;?>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/statistics/good') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/statistics/good">商品分析</a></li>
  </ul>