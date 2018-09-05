<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/
// 在你的控制器实例化之后,任何方法调用之前调用
$hook['post_controller_constructor'] = array(
    'class'    => 'Acl',
    'function' => 'auth',
    'filename' => 'Acl.php',
    'filepath' => 'hooks'
);
//在你的控制器调用之前执行，所有的基础类都已加载，路由和安全检查也已经完成。
$hook['pre_controller']= array(
    'class'    => 'Acl_view',
    'function' => 'router',
    'filename' => 'Acl_view.php',
    'filepath' => 'hooks'
);