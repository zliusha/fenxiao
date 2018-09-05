$(function(){

  var $version = $('#version'),
    $remark = $('#remark'),
    $device_type = $('#device_type'),
    ydb_id = $('#ydb_id').val(),
    download_url = '';

  if (!ydb_id) {
    addYdb();
  } else {
    getYdbInfo();
  }
    $btnConfirm = $("#btn-confirm");

    $btnConfirm.click(function(){
      ydb_status = 0;
    })


  uploadFile('version', {
    browse_button: 'apk-path',
    filters: {
      mime_types: [{
        title: "Apk files",
        extensions: "apk"
      }]
    },
    init: {
      'FileUploaded': function (up, file, info) {
        var res = JSON.parse(info.response);
        var halfpath = res.key;
        var fullpath = up.getOption('domain') + halfpath;
        new Msg({
          type: "success",
          msg: "上传成功"
        });
        $('#apkkey').html(fullpath)
        download_url = fullpath;
      }
    }
  });


    function getYdbInfo(){
      $.getJSON(
        __BASEURL__ + "wm_main_version_api/detail", {
          id: ydb_id
        },
        function (data) {
          if (data.success) {
            $version.val(data.data.main_version_info.version);
            $remark.val(data.data.main_version_info.remark);
            $('[name="type"]').each(function(){
              var val =  $(this).val();
              if(val==data.data.main_version_info.type){
                $(this).attr('checked',true);
              }
            })
            $('[name="is_must"]').each(function(){
              var val =  $(this).val();
              if(val==data.data.main_version_info.is_must){
                $(this).attr('checked',true);
              }
            })
            download_url = data.data.main_version_info.download_url;
            $('#apkkey').html(download_url)
            $("#device_type option[value='" + data.data.main_version_info.device_type + "']").attr(
              "selected",
              "selected"
            );
            addYdb();           
          } else {
            new Msg({
              type: "danger",
              msg: data.msg
            });
          }
        }
      );      
    }


    function addYdb() {
    // 提交信息
    $("#ydb-form")
      .bootstrapValidator({
        fields: {
          version: {
            validators: {
              notEmpty: {
                message: "版本号不能为空"
              },
              regexp: {//正则验证
                regexp: /^\d+(\.\d+)*$/,
                message: '所输入的字符不符要求'
              }
            }
          },
          remark: {
            validators: {
              notEmpty: {
                message: "版本描述不能为空"
              }
            }
          }
        }
      })
      .on("success.form.bv", function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var status = 0,
          version = $version.val(),
          device_type = $device_type.val(),
          is_must = $('[name="is_must"]:checked').val(),
          type = $('[name="type"]:checked').val(),
          remark = $remark.val();

          if(download_url==''){
            new Msg({
              type: "danger",
              msg: '请上传安装包'
            });
            return false
          }

        // 判断是添加或编辑
        if (!ydb_id) {
          post_url = __BASEURL__ + "wm_main_version_api/add";
          post_data = {
            type:type,
            device_type:device_type,
            version:version,
            status:status,
            remark:remark,
            is_must:is_must,
            download_url:download_url
          }
        } else {
          post_url = __BASEURL__ + "wm_main_version_api/edit";
          post_data = {
            id:ydb_id,
            type:type,
            device_type:device_type,
            version:version,
            status:status,
            remark:remark,
            is_must:is_must,
            download_url:download_url
          }
        }

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: "success",
              msg: data.msg,
              delay: 1,
              callback: function () {
                window.location.href = __BASEURL__ + "wm_main_version/index";
              }
            });
          } else {
            new Msg({
              type: "danger",
              msg: data.msg
            });
          }
        });
      });
  }  
})