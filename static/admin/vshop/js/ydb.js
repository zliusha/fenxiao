$(function(){
  var um = UE.getEditor("ydbDetail");

  um.ready(function (editor) {
    console.info(ydb_id)
    // 判断添加或编辑
    if (!ydb_id) {
      addYdb();
    } else {
      getYdbInfo();
    }
  });

  var $ydbDetail = $('#ydbDetail'),
    $ydbTitle = $('#ydb_title'),
    ydb_id = $('#ydb_id').val(),
    ydb_status = '';
    $btnRelease = $('#btn-release'),
    $btnConfirm = $("#btn-confirm");

    $btnRelease.click(function(){
      ydb_status = 1;
    })

    $btnConfirm.click(function(){
      ydb_status = 0;
    })


    function getYdbInfo(){
      $.getJSON(
        __BASEURL__ + "wm_notice_api/detail", {
          id: ydb_id
        },
        function (data) {
          if (data.success) {
            $ydbTitle.val(data.data.m_notice.title);
            um.setContent(data.data.m_notice.content);
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
          ydb_title: {
            validators: {
              notEmpty: {
                message: "公告标题不能为空"
              },
              stringLength: {
                max: 60,
                message: "公告标题不得超过60个字符"
              }
            }
          }
        }
      })
      .on("success.form.bv", function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var status = 0,
          title = $ydbTitle.val(),
          val = um.getContent(),
        content = val;


        if (content == "") {
          new Msg({
            type: "danger",
            msg: "公告详情不能为空"
          });
          return false;
        }



        // 判断是添加或编辑
        if (!ydb_id) {
          post_url = __BASEURL__ + "wm_notice_api/add";
          post_data = {
            title:title,
            content:content,
            status:ydb_status
          }
        } else {
          post_url = __BASEURL__ + "wm_notice_api/edit";
          post_data = {
            id:ydb_id,
            title:title,
            content:content,
            status:ydb_status
          }
        }
        if(ydb_status==0){
          $btnConfirm.prop("disabled", true);
        }else{
          $btnRelease.prop("disabled", true);
        }

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: "success",
              msg: data.msg,
              delay: 1,
              callback: function () {
                window.location.href = __BASEURL__ + "wm_notice/index";
              }
            });
          } else {
            new Msg({
              type: "danger",
              msg: data.msg
            });
            if(ydb_status==0){
              $btnConfirm.prop("disabled", false);
            }else{
              $btnRelease.prop("disabled", false);
            }
          }
        });
      });
  }  
})