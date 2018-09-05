<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="renderer" content="webkit">
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <title>挖到夺宝 后台 - 主页</title>

    <meta name="keywords" content="挖到夺宝-响应式后台">
    <meta name="description" content="挖到夺宝-趣味购物">
    <meta http-equiv="Access-Control-Allow-Origin" content="*">
    <!--[if lt IE 8]>
    <meta http-equiv="refresh" content="0;ie.html" />
    <![endif]-->

    <link rel="shortcut icon" href="favicon.ico">
    <?php echo static_plus_url('bootstrap','bootstrap.min.css');?>
    <?php echo static_plus_url('hplus','font-awesome.min.css');?>
    <?php echo static_plus_url('hplus','animate.min.css');?>
    <?php echo static_plus_url('hplus','style.min.css');?>
    <?php $s_user=$this->session->s_user;?>
</head>

<body class="fixed-sidebar full-height-layout gray-bg" style="overflow:hidden">
    <div id="wrapper">
        <!--左侧导航开始-->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="nav-close"><i class="fa fa-times-circle"></i>
            </div>
            <div class="sidebar-collapse">
                <ul class="nav" id="side-menu">
                    <li class="nav-header">
                        <div class="dropdown profile-element">
                            <span><img alt="image" class="img-circle" width="64px" height="64px" src="<?php echo STATIC_URL.'admin/img/logo.png';?>" /></span>
                            <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                                <span class="clear">
                               <span class="block m-t-xs"><strong class="font-bold"><?php echo $s_user->account;?></strong></span>
                                <span class="text-muted text-xs block"><?php echo $s_user->username;?><b class="caret"></b></span>
                                </span>
                            </a>
                            <ul class="dropdown-menu animated fadeInRight m-t-xs">
                                <li><a class="J_menuItem" href="profile.html">个人资料</a>
                                </li>
                                <li><a class="J_menuItem" href="contacts.html">联系我们</a>
                                </li>
                                <li class="divider"></li>
                                <li><a href="login/out">安全退出</a>
                                </li>
                            </ul>
                        </div>
                        <div class="logo-element">挖
                        </div>
                    </li>
                    <?php 
                        if($s_user->power)
                        {

                            $root=object_find($s_user->power,'p_id',0);
                            $menus=object_find_list($s_user->power,'p_id',$root->id);
                            foreach ($menus as $menu) {
                                echo '<li>';
                                echo "<a href=\"#\"><i class=\"fa fa-cutlery\"></i> <span class=\"nav-label\">{$menu->item_name} </span><span class=\"fa arrow\"></span></a>";
                                $menu_childs=object_find_list($s_user->power,'p_id',$menu->id);
                                if($menu_childs)
                                {
                                    echo '<ul class="nav nav-second-level">';
                                    foreach ($menu_childs as $child) {
                                       echo "<li><a class=\"J_menuItem\" href=\"{$child->item_value}\">{$child->item_name}</a></li>";
                                    }
                                    echo '</ul>';
                                }
                                echo '</li>';
                            }
                        }
                    ?>
                </ul>
            </div>
        </nav>
        <!--左侧导航结束-->
        <!--右侧部分开始-->
        <div id="page-wrapper" class="gray-bg dashbard-1">
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header"><a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                        <form role="search" class="navbar-form-custom" method="post" action="search_results.html">
                            <div class="form-group">
                                <input type="text" placeholder="请输入您需要查找的内容 …" class="form-control" name="top-search" id="top-search">
                            </div>
                        </form>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">
                        <li class="dropdown hidden-xs">
                            <a class="right-sidebar-toggle" aria-expanded="false">
                                <i class="fa fa-tasks"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="row content-tabs">
                <button class="roll-nav roll-left J_tabLeft"><i class="fa fa-backward"></i>
                </button>
                <nav class="page-tabs J_menuTabs">
                    <div class="page-tabs-content">
                        <a href="javascript:;" class="active J_menuTab" data-id="index_v1.html">首页</a>
                    </div>
                </nav>
                <button class="roll-nav roll-right J_tabRight"><i class="fa fa-forward"></i>
                </button>
                <div class="btn-group roll-nav roll-right">
                    <button class="dropdown J_tabClose" data-toggle="dropdown">关闭操作<span class="caret"></span>

                    </button>
                    <ul role="menu" class="dropdown-menu dropdown-menu-right">
                        <li class="J_tabShowActive"><a>定位当前选项卡</a>
                        </li>
                        <li class="divider"></li>
                        <li class="J_tabCloseAll"><a>关闭全部选项卡</a>
                        </li>
                        <li class="J_tabCloseOther"><a>关闭其他选项卡</a>
                        </li>
                    </ul>
                </div>
                <a href="login" class="roll-nav roll-right J_tabExit"><i class="fa fa fa-sign-out"></i> 退出</a>
            </div>
            <div class="row J_mainContent" id="content-main">
                <iframe class="J_iframe" name="iframe0" width="100%" height="100%" src="<?php echo c_site_url('welcome/sys_default'); ?>" frameborder="0" data-id="index_v1.html" seamless></iframe>
            </div>
            <div class="footer">
                <div class="pull-right">&copy; 2016-2016 挖到夺宝
                </div>
            </div>
        </div>
        <!--右侧部分结束-->

    </div>
    <?php echo static_plus_url('jquery','jquery.min.js')?>
    <?php echo static_plus_url('bootstrap','bootstrap.min.js')?>
    <?php echo static_plus_url('hplus','plugins/metisMenu/jquery.metisMenu.js')?>
    <?php echo static_plus_url('hplus','plugins/slimscroll/jquery.slimscroll.min.js')?>
    <?php echo static_plus_url('hplus','plugins/layer/layer.min.js')?>
    <?php echo static_plus_url('hplus','hplus.min.js')?>
    <?php echo static_plus_url('hplus','contabs.min.js')?>
    <?php echo static_plus_url('hplus','plugins/pace/pace.min.js')?>
</body>

</html>