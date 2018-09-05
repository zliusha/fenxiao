 <?php
// 开发环境配置
define('STATIC_V', 'v1.1.39'); // 静态文件版本号
define('VERSION', 'v1.2.7'); // 产品版本号

define('ENCRYPTION_KEY','f1a25eed70f93d51e7d48e3aef9d5085');    //加密
//　cookie预安全码
define('SECRET_COOKIE_KEY', '01802231ffb74acbe6953bee2af58445');

define('ADMIN_URL', URL_SCHEME . 'admin.waimai.com/');
define('DJADMIN_URL', URL_SCHEME . 'fw.waimai.com/');
// define('UPLOAD_URL', 'http://oydp172vs.bkt.clouddn.com/');
define('UPLOAD_URL', 'http://img1.waimaishop.com/');
define('LOCAL_UPLOAD_URL',URL_SCHEME . 'upload.waimai.com/');
define('STATIC_URL', URL_SCHEME . 'static.waimai.com/');
define('SITE_URL', URL_SCHEME . 'www.waimai.com/');
define('API_URL',URL_SCHEME . 'api.waimai.com/');
define('ERP_API_URL', 'http://121.199.182.2/');
define('SCRM_URL', 'https://appscrmdev.ecbao.cn/');
define('WS_URL', 'ws://121.41.177.151:30002');

//saas中心
define('SAAS_URL', URL_SCHEME . 'saas.waimai.com/');
//一店一码
define('YDYM_URL', URL_SCHEME . 'yddm.waimai.com/');
//商圈
define('HCITY_URL', URL_SCHEME . 'hcity.waimai.com/');

//一店一码h5url
define('M_HCITY_URL', URL_SCHEME . 'mhcity.waimai.com/');
//合伙人后台
define('HCITY_ADMIN_URL', URL_SCHEME . 'hadmin.waimai.com/');

// COOKIE作用域
define('COOKIE_DOMAIN', '.waimai.com');
define('M_SUB_URL', 'm.waimai.com');
