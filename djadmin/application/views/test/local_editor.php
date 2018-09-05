<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html>
<?=static_original_url('libs/umeditor/1.2.2/themes/default/css/umeditor.css');?>
<head>
    <title>测试七牛上传</title>
</head>
<body>
<script id="goodDetail" type="text/plain" style="width:560px;height:360px;"></script>
</body>
<script src="http://static.wsc.com/libs/jquery/3.2.1/jquery.min.js?v=v1.0.0.1" type="text/javascript" charset="utf-8"></script>
<script src="http://www.wsc.com/static/qiniu_ueditor_1.4.3/ueditor.config.js?v=v1.0.0.1" type="text/javascript" charset="utf-8"></script>
<script src="http://www.wsc.com/static/qiniu_ueditor_1.4.3//ueditor.all.min.js?v=v1.0.0.1" type="text/javascript" charset="utf-8"></script>
<script src="http://www.wsc.com/static/qiniu_ueditor_1.4.3/lang/zh-cn/zh-cn.js?v=v1.0.0.1" type="text/javascript" charset="utf-8"></script>

<script type="text/javascript">
  var um = UE.getEditor('goodDetail');
</script>
</html>