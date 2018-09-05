<?php
/**
 * @Author: binghe
 * @Date:   2018-05-16 17:07:05
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-05-16 17:07:26
 */
namespace Service\Traits;

trait ControllerTrait
{
    public function __get($name)
    {
        $instance = \CI_Controller::get_instance();
        return $instance->{$name} ?? null;
    }
}