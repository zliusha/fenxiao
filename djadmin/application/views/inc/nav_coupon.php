<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><div class="btn-group mb20" role="group">
    <a class="btn btn-default <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/promotion/coupon_list') !== false):?>active<?php endif;?>" href="<?=DJADMIN_URL?>mshop/promotion/coupon_list">裂变优惠券</a>
    <a class="btn btn-default <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/promotion/coupon_follow') !== false):?>active<?php endif;?>" href="<?=DJADMIN_URL?>mshop/promotion/coupon_follow">关注优惠券</a>
  </div>