<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>微商城 - 管理后台</title>
    <?php $this->load->view('inc/global_header'); ?>
    <?= static_original_url('admin/css/account.min.css'); ?>
    <?= static_original_url('libs/bootstrap-validator/2.0/css/bootstrapValidator.min.css'); ?>
</head>
<body>
<header id="w-header" class="w-header">
    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar"
                        aria-expanded="false">
                    <span class="sr-only">切换导航</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?= ADMIN_URL ?>">
                    <img src="<?= STATIC_URL ?>admin/img/logo-primary.png" alt="挖到">
                </a>
            </div>
            <div class="collapse navbar-collapse" id="main-navbar">
                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" role="button"
                           aria-haspopup="true" aria-expanded="false" style="line-height: 36px;">
                            <img class="avatar" src="" alt=""><span id="username"></span><span
                                    class="iconfont icon-arrow-down"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <!-- <li class="J_NAV_ITEM"><a href="javascript:;"><span class="iconfont icon-info"></span>部门：</a></li> -->
                            <li><a href="<?= ADMIN_URL ?>passport/logout"><span
                                            class="iconfont icon-quit"></span>退出登录</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<aside id="w-aside" class="w-aside">
    <ul class="nav">
        <li class="J_NAV_ITEM active">
            <a href="<?= ADMIN_URL ?>home/index"><span class="iconfont icon-gaikuang"></span>首页</a>
        </li>
        <li>
            <a class="J_TOGGLE_SUBNAV" href="javascript:;"><span class="iconfont icon-shop"></span>系统公告<span
                        class="iconfont icon-arrow-down"></span></a>
            <ul class="subnav">
                <li class="J_NAV_ITEM">
                    <a href="<?= ADMIN_URL ?>wm_notice/index">云店宝公告</a>
                </li>
<!--                <li class="J_NAV_ITEM">-->
<!--                    <a href="--><?//= ADMIN_URL ?><!--Wsc_main_article/index">微商城公告</a>-->
<!--                </li>-->
            </ul>
        </li>
        <li class="J_NAV_ITEM">
            <a href="<?= ADMIN_URL ?>erp/index"><span class="iconfont icon-order"></span>用户查询</a>
        </li>
        <li class="J_NAV_ITEM">
            <a href="<?= ADMIN_URL ?>elm/index"><span class="iconfont icon-order"></span>商家查询</a>
        </li>
        <li>
            <a class="J_TOGGLE_SUBNAV" href="javascript:;"><span class="iconfont icon-shop"></span>小程序管理<span class="iconfont icon-arrow-down"></span></a>
            <ul class="subnav">
                <li class="J_NAV_ITEM">
                    <a href="<?=ADMIN_URL?>wm_main_xcx_version/index">版本列表</a>
                </li>
            </ul>
        </li>
        <li class="J_NAV_ITEM">
            <a href="<?= ADMIN_URL ?>wm_main_version/index"><span class="iconfont icon-order"></span>版本信息</a>
        </li>
    </ul>
</aside>
<section id="w-content" class="w-content">
    <iframe id="main-frame" src="<?= ADMIN_URL ?>home/index" frameborder="0"
            style="width: 100%;height: 100%;border: 0;"></iframe>
    <div id="loading-box" class="loading-box">
        <div class="loading">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
</section>
<?php $this->load->view('inc/global_footer'); ?>
<?= static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js'); ?>
<?= static_original_url('admin/js/main.min.js'); ?>
<?= static_original_url('admin/js/index.min.js'); ?>
</body>
</html>
<script>
    $(function () {
        function getAccountInfo() {
            $.getJSON(__BASEURL__ + "/sys_account_api/info", {},
                function (data) {
                    if (data.success) {
                        $('#username').html(data.data.m_account.username);
                    } else {
                        new Msg({
                            type: "danger",
                            msg: data.msg
                        });
                    }
                }
            );
        }

        getAccountInfo();
    });
</script>
