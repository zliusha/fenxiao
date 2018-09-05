<!DOCTYPE html>
<html>
<?php include_head('首页');?>
<body class="list-page">
<div class="container">
    <div class="center-block" style="margin: 20% 20% 0;font-size: 60px;font-weight: 200;line-height: 1;letter-spacing: -1px;">
        你好<?=$username?>，欢迎您！
    </div>
</div>

<?php echo static_plus_url('jquery','jquery.min.js');
echo static_plus_url('bootstrap','bootstrap.min.js');
echo static_plus_url('bootstrap-table','bootstrap-table.min.js');
echo static_plus_url('bootstrap-table','bootstrap-table-zh-CN.min.js');
echo static_site_url('admin','bh_common.js');
echo static_original_url('plugins/layer/layer.js');?>

</body>
</html>