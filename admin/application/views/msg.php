<!DOCTYPE html>
<html>
<?php include_head('过度消息页面');
    
    //可设置
    //m_msg 消息
    //m_router_key　路由key
    //m_type btn位值　位 1=>list,2=>add,3=>edit
    //m_is_timeout 是否倒计时
    //m_success
    $success = false;
    if(isset($m_success) && is_bool($m_success))
        $success=$m_success;
    $is_timeout=true;
    if(isset($m_is_timeout) && is_bool($m_is_timeout))
        $is_timeout=$m_is_timeout;
    
    $msg='--';
    if(isset($m_msg))
        $msg=$m_msg;

    // $router_crud = &inc_config('admin_router_crud');
    // $dir='';
    // foreach ($router_crud as $key => $crud) {
    //    if($dir!='')
    //         break;
    //    foreach ($crud as $v) {
    //       if($v == $this->router->class)
    //       {
    //         $dir=$key.'-';
    //         break;
    //       }
    //    }
    // }
    //$type 位 1=>list,2=>add,3=>edit
    $type=0;
    if(!isset($m_type))
    {
        $method=$this->router->method;
        $t_str = strrchr($method,'_add');
        if(strlen($t_str) == strlen('_add'))
            $type=6;

        $t_str = strrchr( $method ,'_edit' );
        if(strlen($t_str) == strlen('_edit'))
            $type=10;
    }
    else $type=$m_type;

    $id=0;
    if(isset($m_id))
        $id=$m_id;



    // 跳转
    $add_url=$edit_url=$list_url=$default_back='';

        //列表
        if(isset($m_router) && isset($m_router['list']))
            $list_url= c_site_url($m_router['list']);
        else
            $list_url= c_site_url(''.$this->url_class.'/grid_list');
        //添加
        if(isset($m_router) && isset($m_router['add']))
            $add_url= c_site_url($m_router['add']);
        else
            $add_url= c_site_url(''.$this->url_class.'/form_add');

        if(isset($m_router) && isset($m_router['edit']))
            $edit_url= c_site_url($m_router['edit']);
        else
            $edit_url= c_site_url(''.$this->url_class.'/form_edit/'.$id);

        if(isset($m_router) && isset($m_router['back']))
            $default_back= c_site_url($m_router['back']);
        else
            $default_back= $list_url;

    $close = true;
    if(isset($m_close)) $close=$m_close;

?>
<body class="list-page">
    <div class="animated fadeInRight ibox-fluid">
      <div class="ibox-title">
          <h5>基本 <small>消息提示</small></h5>
      </div>
      <div class="ibox-content">
          <div style='width: 50%;margin: 0 auto;margin-top: 15%;height: 150px;'>
                <?php if(isset($msg)):?>

                <div class='alert <?php echo $success?'alert-success':'alert-danger';?>'>
                    <?php echo $msg;?>
                    <br/> 
                    <span id='showbox'>5</span> 秒后自动转向...
                </div>
               <?php endif;?>
                <div class='btn-group'>
                <?php
                    if($type & 1<<1 && $close==true):?>
                    <a class='btn btn-info' href="<?php echo $list_url;?>" target="_self"> 回到列表页 </a>
                    <?php endif;

                    if($type & 1<<2):?>
                    <a class='btn btn-default' href="<?php echo $add_url;?>" target="_self"> 继续添加 </a>
                    <?php endif;

                    if($type & 1<<3):?>
                    <a class='btn btn-default' href="<?php echo $edit_url;?>" target="_self"> 重新编辑 </a>
                    <?php endif;
                ?>
                </div>
          </div>
      </div>
   </div>
<?php include_views('inc_js.php');?>
<script type="text/javascript" charset="utf-8">
    var close = '<?php echo $close;?>';
var timeout = 4;
function show() {
    var showbox = $("#showbox");
    showbox.html(timeout);
    timeout--;
    if (timeout == 0) {
        if(close == false) parent.layer.closeAll();
        else window.location.href = "<?php echo $default_back;?>";
    }
    else {
        setTimeout("show()", 1000);
    }
}
<?php if($is_timeout):?>
setTimeout("show()",1000);
<?php endif;?>
</script>
</body>
</html>