<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><ul class="nav nav-tabs">
    <?php if($this->is_zongbu):?>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/index') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/setting/index">商城设置</a></li>
      <?php if( power_exists(module_enum::WM_MODULE,$this->service->power_keys)):?>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_shop') !== false || strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_dada') !== false || strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_dianwoda') !== false || strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_fengda') !== false || strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_cantingbao') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/setting/delivery_shop">配送配置</a></li>
      <?php endif;?>
      <?php if( power_exists(module_enum::MEAL_MODULE,$this->service->power_keys)):?>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/meal') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/setting/meal">堂食配置</a></li>
      <?php endif;?>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/wechat') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/setting/wechat">公众号配置</a></li>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/alipay') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/setting/alipay">支付宝配置</a></li>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/fubei_shop') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/setting/fubei_shop">银行通道配置</a></li>
      <!-- <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/account') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/setting/account">子账号管理</a></li> -->
    <?php else:?>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/printer') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/setting/printer">打印机</a></li>
    <?php endif;?>
  </ul>