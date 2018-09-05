<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html>
<head>
    <title>测试七牛上传</title>
</head>
<body>
<form method="post" action="http://upload.qiniu.com/" enctype="multipart/form-data">
    <input type="file" name="file">
    <input type="hidden" name="key" value="<?=$key;?>">
    <input type="hidden" name="token" value="<?=$token;?>">
    <button  type="submit" class="btn btn-primary btn-block">上传</button>
</form>
</body>
</html>