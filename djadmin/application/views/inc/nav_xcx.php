<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><ul class="nav nav-tabs">
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/xcx_config/index') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/xcx_config/index">小程序授权</a></li>
    <li role="presentation" <?php if(strpos($_SERVER['REQUEST_URI'], '/mshop/xcx_config/banner') !== false):?>class="active"<?php endif;?>><a href="<?=DJADMIN_URL?>mshop/xcx_config/banner">小程序装修</a></li>
  </ul>