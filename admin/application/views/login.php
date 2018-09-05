<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

    <title>挖到夺宝 - 登录</title>
    <meta name="keywords" content="挖到夺宝-响应式后台">
    <meta name="description" content="挖到夺宝-趣味购物">
    <?php echo static_plus_url('bootstrap','bootstrap.min.css');?>
    <?php echo static_plus_url('hplus','font-awesome.min.css');?>
    <?php echo static_plus_url('hplus','animate.min.css');?>
    <?php echo static_plus_url('hplus','style.min.css');?>
    <?php echo static_plus_url('hplus','login.min.css');?>
    <?php echo static_site_url('admin','style.css');?>
    <!--[if lt IE 8]>
    <meta http-equiv="refresh" content="0;ie.html" />
    <![endif]-->
    <script>
        if(window.top!==window.self){window.top.location=window.location};
    </script>

</head>

<body class="signin">
    <div class="signinpanel">
        <div class="row">
            <div class="col-sm-7">
                <div class="signin-info">
                    <div class="logopanel m-b">
                        <h1>[ 挖到夺宝 ]</h1>
                    </div>
                    <div class="m-b"></div>
                    <h4>欢迎使用 <strong>挖到夺宝</strong></h4>
                    <ul class="m-b">
                        <li><i class="fa fa-arrow-circle-o-right m-r-xs"></i> 趣味购物</li>
                        <li><i class="fa fa-arrow-circle-o-right m-r-xs"></i> 公平公正</li>
                        <li><i class="fa fa-arrow-circle-o-right m-r-xs"></i> 规则公布</li>
                        <li><i class="fa fa-arrow-circle-o-right m-r-xs"></i> 完美体验</li>
                        <li><i class="fa fa-arrow-circle-o-right m-r-xs"></i> 售后服务</li>
                    </ul>
                </div>
            </div>
            <div class="col-sm-5">
                
                <?php echo form_open('',array("class"=>"form-horizontal","id"=>"frm-login"))?>
                    <p class="m-t-md">登录挖到夺宝管理系统</p>
                    <?php echo validation_errors();?>
                    <?php if(isset($message)) echo $message;?>
                    <input type="text" class="form-control uname" value="<?php echo set_value('account') ?>" placeholder="用户名" id="account" name="account" />
                    <input type="password" class="form-control pword" placeholder="密码" id="password" name='password' />
                    <button class="btn btn-success btn-block">登录</button>
                </form>

            </div>
        </div>
        <div class="signup-footer">
            <div class="pull-left">
                &copy; 2016 All Rights Reserved. 挖到夺宝
            </div>
        </div>
    </div>
    <?php echo static_plus_url('jquery','jquery.min.js')?>
    <?php echo static_plus_url('jquery','jquery.validate.min.js')?>
    <?php echo static_plus_url('jquery','additional-methods.js')?>
    <?php echo static_plus_url('jquery','messages_zh.min.js')?>
    <?php echo static_site_url('admin','bh_common.js');?>
    <script type="text/javascript">
    $(function(){
        var _rules={
            account:"required",
            password:{
                required:true,
                pregRule:PregRule.Pwd
            }
        };
        var _message = {};
        $('#frm-login').validate({rules:_rules,messages:_message});
    });
    </script>
</body>
    
</html>