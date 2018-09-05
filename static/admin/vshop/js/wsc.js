$(function(){
  var um = UE.getEditor("wscDetail");

  um.ready(function (editor) {
    console.info(wsc_id)
    // 判断添加或编辑
    if (!wsc_id) {
      addWsc();
    } else {
      getWscInfo();
    }
  });

  var $wscDetail = $('#wscDetail'),
    $wscTitle = $('#wsc_title'),
    wsc_id = $('#wsc_id').val(),
    wsc_status = '';
    $btnRelease = $('#btn-release'),
    $btnConfirm = $("#btn-confirm");

    $btnRelease.click(function(){
      wsc_status = 1;
    })

    $btnConfirm.click(function(){
      wsc_status = 0;
    })


    function getWscInfo(){
      $.getJSON(
        __BASEURL__ + "wsc_main_article_api/detail", {
          id: wsc_id
        },
        function (data) {
          if (data.success) {
            $wscTitle.val(data.data.title);
            um.setContent(data.data.content);
            $('[name="wsc_type"]').each(function(){
              var val =  $(this).val();
              if(val==data.data.cate){
                $(this).attr('checked',true);
              }
            })
            addWsc();           
          } else {
            new Msg({
              type: "danger",
              msg: data.msg
            });
          }
        }
      );      
    }


    function addWsc() {
    // 提交信息
    $("#wsc-form")
      .bootstrapValidator({
        fields: {
          wsc_title: {
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
          title = $wscTitle.val(),
          val = um.getContent(),
          cate =[],
          content = val;


        if (content == "") {
          new Msg({
            type: "danger",
            msg: "公告详情不能为空"
          });
          return false;
        }

        $('[name="wsc_type"]:checked').each(function (i, el) {
          var attrId = '';
          attrId = $(el).val();
          if (attrId) {
            cate.push(attrId);
          }
        });


        // 判断是添加或编辑
        if (!wsc_id) {
          post_url = __BASEURL__ + "wsc_main_article_api/add";
          post_data = {
            title:title,
            content:content,
            state:wsc_status,
            cate:cate.toString(),
            is_index:wsc_status
          }
        } else {
          post_url = __BASEURL__ + "wsc_main_article_api/edit";
          post_data = {
            id:wsc_id,
            title:title,
            content:content,
            state:wsc_status,
            cate:cate.toString(),
            is_index:wsc_status
          }
        }
        if(wsc_status==0){
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
                window.location.href = __BASEURL__ + "wsc_main_article/index";
              }
            });
          } else {
            new Msg({
              type: "danger",
              msg: data.msg
            });
            if(wsc_status==0){
              $btnConfirm.prop("disabled", false);
            }else{
              $btnRelease.prop("disabled", false);
            }
          }
        });
      });
  }  
})