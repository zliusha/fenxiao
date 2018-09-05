<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><div class="btn-group mb20" role="group">
    <a class="btn btn-default <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_shop') !== false):?>active<?php endif;?>" href="<?=DJADMIN_URL?>mshop/setting/delivery_shop">门店配送</a>
    <a class="btn btn-default <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_dada') !== false):?>active<?php endif;?>" href="<?=DJADMIN_URL?>mshop/setting/delivery_dada">达达配送</a>
    <a class="btn btn-default <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_dianwoda') !== false):?>active<?php endif;?>" href="<?=DJADMIN_URL?>mshop/setting/delivery_dianwoda">点我达配送</a>
    <a class="btn btn-default <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_fengda') !== false):?>active<?php endif;?>" href="<?=DJADMIN_URL?>mshop/setting/delivery_fengda">风达配送</a>
    <a class="btn btn-default <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/setting/delivery_cantingbao') !== false):?>active<?php endif;?>" href="<?=DJADMIN_URL?>mshop/setting/delivery_cantingbao">餐厅宝配送</a>
  </div>