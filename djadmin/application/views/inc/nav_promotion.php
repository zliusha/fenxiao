<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><ul class="nav nav-tabs">
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/promotion/discount_list') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/promotion/discount_list">限时折扣</a></li>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/promotion/activity_list') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/promotion/activity_list">满减活动</a></li>
    <?php if($this->is_zongbu): ?>
      <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/promotion/coupon_list') !== false || strpos($_SERVER['REQUEST_URI'], '/mshop/promotion/coupon_follow') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/promotion/coupon_list">优惠劵</a></li>
    <?php endif; ?>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/promotion/new_user') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/promotion/new_user">新用户优惠</a></li>
  </ul>