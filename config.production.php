<?php
// 正式环境配置
define('STATIC_V', 'v1.1.39'); // 静态文件版本号
define('VERSION', 'v1.2.7'); // 产品版本号

define('ENCRYPTION_KEY','f1a25eed70f93d51e7d48e3aef9d5083');    //加密
//　cookie预安全码
define('SECRET_COOKIE_KEY', '01802231ffb74acbe6953bee2af58443');

define('ADMIN_URL', URL_SCHEME . 'admin.waimaishop.com/');
define('DJADMIN_URL', URL_SCHEME . 'fw.waimaishop.com/');
// define('UPLOAD_URL', 'http://ozt666mgn.bkt.clouddn.com/');
define('UPLOAD_URL', 'http://img.waimaishop.com/');
define('LOCAL_UPLOAD_URL',URL_SCHEME . 'upload.waimaishop.com/');
define('STATIC_URL', URL_SCHEME . 'static.m.waimaishop.com/');
define('SITE_URL', URL_SCHEME . 'www.waimaishop.com/');
define('API_URL',URL_SCHEME . 'api.waimaishop.com/');
define('ERP_API_URL', 'http://oa.ecbao.cn/');
define('SCRM_URL', 'https://appscrm.ecbao.cn/');
define('WS_URL', WS_SCHEME.'fw.waimaishop.com/mq/');

//saas中心
define('SAAS_URL', URL_SCHEME .'saas.waimaishop.com/');
//一店一码
define('YDYM_URL', URL_SCHEME .'ydym.waimaishop.com/');
//商圈
define('HCITY_URL', URL_SCHEME .'hcity.waimaishop.com/');
//一店一码h5url
define('M_HCITY_URL', 'http://mhcity.waimaishop.com/');
//合伙人后台
define('HCITY_ADMIN_URL', 'http://hadmin.waimaishop.com/');
// COOKIE作用域
define('COOKIE_DOMAIN', '.waimaishop.com');
define('M_SUB_URL', 'm.waimaishop.com');
