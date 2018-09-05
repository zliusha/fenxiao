<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><ul class="nav nav-tabs">
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/finance/index') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/finance/index">资金结算</a></li>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/finance/withdraw_list') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/finance/withdraw_list">提现列表</a></li>
  </ul>