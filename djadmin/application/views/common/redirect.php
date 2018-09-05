<html>
    <?php
        //默认配置
        $_msg='跳转中';
        $_second=3;
        $_url=SITE_URL.'passport/login';
        $_is_redirect=true;
        //显示消息
        if(isset($msg))
            $_msg=$msg;
        //跳转停留时间
        if(isset($second))
            $_second=$second;
        //跳转url
        if(isset($url))
            $_url=$url;
        //是否跳转 默认
        if(isset($is_redirect))
            $_is_redirect=$is_redirect;
    ?>
    <head>
        <?php if($_is_redirect):?>
            <meta http-equiv="refresh" content="<?=$_second?>;url=<?=$_url?>">
        <?php endif;?>
    </head>
    <body>
        <p><?=$_msg?></p>
    </body>
</html>