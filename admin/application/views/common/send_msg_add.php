<!DOCTYPE html>
<html>

<?php include_head('广告列表');?>
<body class="list-page">
<div class="animated fadeInRight ibox-fluid">
    <div class="ibox-title">
        <h5>当前位置：<a href="<?php echo c_site_url('linshi/grid_list');?>">短信列表</a>->发送短信</h5>
    </div>
    <div class="ibox-content">
        <?php echo form_open('common_operate/send_msg_add',array("class"=>"form-horizontal","id"=>"frm-add")); ?>
        <div class="form-group">
            <label for="mobiles" class="col-sm-2 control-label">手机号码</label>
            <div class="col-sm-3">
                <textarea  class="form-control" id="mobiles" onkeyup="this.value=this.value.replace(/[^1-9\,]/g,'')"  name="mobiles" ></textarea>
            </div>
            <label id="mobiles-error" class="error col-sm-2" for="mobiles"></label>
        </div>
        <div class="form-group">
            <label for="mobile_file" class="col-sm-2 control-label"></label>
            <div class="col-sm-3">
                <input type="text" class="form-control" name="mobile_file" readonly id="mobile_file" />
            </div>
            <div class="col-sm-1">
                <input type="button" class="btn btn-default" id="uploadButton" value="上传用户数据" />
            </div>
            <label id="mobile_file-error" class="error col-sm-2" for="mobile_file"></label>
        </div>
        <div class="form-group">
            <label for="content" class="col-sm-2 control-label"><span class="red">*</span>模板内容</label>
            <div class="col-sm-3">
                <textarea  class="form-control" id="content" name="content" ></textarea>
            </div>
            <label id="content-error" class="error col-sm-2" for="content"></label>
        </div>

        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-primary">发送短信</button>
            </div>
        </div>
        </form>
    </div>
</div>
<?php include_views('inc_js.php');?>

<?php echo static_original_url('plugins/kindeditor/kindeditor-all-min.js');?>
<?php echo static_original_url('plugins/kindeditor/lang/zh-CN.js');?>
<?php echo static_original_url('plugins/kindeditor/themes/default/default.css');?>
<?php echo static_plus_url('jquery','jquery.validate.min.js')?>
<?php echo static_plus_url('jquery','additional-methods.js')?>
<?php echo static_plus_url('jquery','messages_zh.min.js')?>
<script type="text/javascript">
KindEditor.ready(function(K) {
    var upload_button = K.uploadbutton({
        button : K('#uploadButton')[0],
        fieldName : 'imgFile',
        url : '<?php echo c_site_url('common_file/upload?type=excel');?>',
        afterUpload : function(data) {
            console.log(data);
            if (data.error === 0) {
                K('#mobile_file').val(data.url);
            } else {
                alert(data.message);
            }
        },
        afterError : function(str) {
            alert('自定义错误信息: ' + str);
        }
    });
    upload_button.fileBox.change(function(e) {
        upload_button.submit();
    });
});
$(function(){
    $('#frm-add').submit(function(){

        var mobiles = $('#mobiles').val();
        var mobile_file = $('#mobile_file').val();
        var content = $('#content').val();

        console.log(mobiles+'===='+mobile_file+"===="+content);
        if(mobile_file == '' && mobiles == ''){
            layer.msg('手机号码输入框和上传用户数据至少一个不为空', {icon: 2});
            return false;
        }
        if(content == ''){
            //$('#content-error').text('必填');
            layer.msg('模板内容不能空', {icon: 2});
            return false;
        }
        return true;
    });
});
</script>
</body>
</html>