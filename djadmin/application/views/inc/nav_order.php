<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><ul class="nav nav-tabs">
	<?php if(power_exists(module_enum::WM_MODULE,$this->service->power_keys)):?>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/order/index') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/order/index">外卖订单</a></li>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/order/pickup') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/order/pickup">自提订单</a></li>
	<?php endif;?>
	<?php if(power_exists(module_enum::MEAL_MODULE,$this->service->power_keys)):?>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/order/dinein') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/order/dinein">堂食订单</a></li>
    <?php endif;?>
    <?php if(power_exists(module_enum::LS_MODULE,$this->service->power_keys)):?>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/order/retail') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/order/retail">零售订单</a></li>
    <?php endif;?>
  </ul>