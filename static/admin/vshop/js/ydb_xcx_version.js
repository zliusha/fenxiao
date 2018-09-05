$(function(){

  var $ydb_xcx_version = $('#ydb_xcx_version'),
    $ydb_xcx_template_id = $('#ydb_xcx_template_id'),
    $ydb_xcx_type = $('#ydb_xcx_type'),
    $ydb_xcx_user_desc = $('#ydb_xcx_user_desc'),
    ydb_id = $('#ydb_id').val(),
    ydb_status = '';

  if (!ydb_id) {
    addYdb();
  } else {
    getYdbInfo();
  }
    $btnConfirm = $("#btn-confirm");

    $btnConfirm.click(function(){
      ydb_status = 0;
    })


    function getYdbInfo(){
      $.getJSON(
        __BASEURL__ + "wm_main_xcx_version_api/detail", {
          id: ydb_id
        },
        function (data) {
          if (data.success) {
            $ydb_xcx_version.val(data.data.main_xcx_version_info.user_version);
            $ydb_xcx_template_id.val(data.data.main_xcx_version_info.template_id);
            $ydb_xcx_type.val(data.data.main_xcx_version_info.type);
            $ydb_xcx_user_desc.val(data.data.main_xcx_version_info.user_desc);
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
          ydb_xcx_version: {
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
          ydb_xcx_template_id: {
            validators: {
              notEmpty: {
                message: "模板ID不能为空"
              },
              stringLength: {
                max: 60,
                message: "模板ID不得超过11个字符"
              },
              digits: {
                message: '该值只能包含数字。'
              }
            }
          },
          ydb_xcx_type: {
            validators: {
              notEmpty: {
                message: "类型不能为空"
              }
            }
          },
          ydb_xcx_user_desc: {
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
          xcx_version = $ydb_xcx_version.val(),
          xcx_template_id = $ydb_xcx_template_id.val(),
          xcx_type = $ydb_xcx_type.val(),
          xcx_user_desc = $ydb_xcx_user_desc.val();

        // 判断是添加或编辑
        if (!ydb_id) {
          post_url = __BASEURL__ + "wm_main_xcx_version_api/add";
          post_data = {
            type:xcx_type,
            user_version:xcx_version,
            template_id:xcx_template_id,
            user_desc:xcx_user_desc
          }
        } else {
          post_url = __BASEURL__ + "wm_main_xcx_version_api/edit";
          post_data = {
            id:ydb_id,
            type:xcx_type,
            user_version:xcx_version,
            template_id:xcx_template_id,
            user_desc:xcx_user_desc
          }
        }

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: "success",
              msg: data.msg,
              delay: 1,
              callback: function () {
                window.location.href = __BASEURL__ + "wm_main_xcx_version/index";
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