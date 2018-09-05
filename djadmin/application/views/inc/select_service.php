<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><select id="service" class="form-control" name="service">
    <option value="">全部服务</option>
    <?php if( power_exists(module_enum::WM_MODULE,$this->service->power_keys)):?>
      <option value="<?=module_enum::WM_MODULE?>">外卖</option>
    <?php endif;?>
    <?php if( power_exists(module_enum::MEAL_MODULE,$this->service->power_keys)):?>
      <option value="<?=module_enum::MEAL_MODULE?>">堂食</option>
    <?php endif;?>
    <?php if( power_exists(module_enum::LS_MODULE,$this->service->power_keys)):?>
      <option value="<?=module_enum::LS_MODULE?>">零售</option>
    <?php endif;?>
  </select>